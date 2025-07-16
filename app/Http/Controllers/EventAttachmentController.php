<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Event\AttachmentRequest;


class EventAttachmentController extends Controller
{
    private function authenticateUser(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) {
            Log::warning('No Bearer Token provided', [
                'method' => $request->method(),
                'url'    => $request->fullUrl(),
                'ip'     => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            Log::warning('Invalid or expired token', ['token' => $token]);
            return null;
        }

        return $accessToken->tokenable_type === User::class
            ? User::find($accessToken->tokenable_id)
            : null;
    }

    public function index(Request $request, $eventId): JsonResponse
    {
        $user = $this->authenticateUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $event = Event::findOrFail($eventId);
        return response()->json([
            'data' => $event->attachments,
            'message' => 'Attachments retrieved successfully',
        ], Response::HTTP_OK);
    }

    public function store(AttachmentRequest $request): JsonResponse
    {
        // Xác thực người dùng
        $user = $this->authenticateUser($request);
        if (!$user) {
            Log::warning('Unauthorized access attempt', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->sendError('Không được phép', Response::HTTP_UNAUTHORIZED);
        }

        // Log toàn bộ request để kiểm tra
        Log::info('Received file upload request', [
            'files' => $request->allFiles(),
            'headers' => $request->headers->all(),
            'input' => $request->all(),
        ]);

        // Validate mảng file
        $validated = $request->validated();

        // Lấy danh sách file từ request
        $files = $validated['files'] ?? [];
        $uids = $validated['uids'] ?? [];

        // Kiểm tra số lượng uids khớp với files
        if (count($files) !== count($uids)) {
            Log::warning('Mismatched files and uids count', [
                'files_count' => count($files),
                'uids_count' => count($uids),
            ]);
            return $this->sendError('Số lượng UID không khớp với số lượng tệp.', Response::HTTP_BAD_REQUEST);
        }

        // Xử lý từng file
        $attachments = [];
        DB::beginTransaction();
        try {
            foreach ($files as $index => $file) {
                $fileUrl = $file->store('attachments', 'public');
                $uid = $uids[$index];

                $attachment = Attachment::create([
                    'uid' => $uid,
                    'file_name' => $file->getClientOriginalName(),
                    'file_url' => Storage::url($fileUrl),
                    'file_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploader_id' => $user->id,
                ]);

                $attachments[] = [
                    'id' => $attachment->id,
                    'uid' => $attachment->uid,
                    'file_name' => $attachment->file_name,
                    'file_url' => $attachment->file_url,
                    'file_type' => $attachment->file_type,
                    'size' => $attachment->size,
                ];
            }

            DB::commit();
            return $this->sendSuccess(['attachments' => $attachments], "Tải tệp thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Error", [
                "error" => $e
            ]);
            return $this->sendError("Có lỗi xảy ra. Xin hãy vui lòng thử lại sau!", 500);
        }
    }

    public function show(Request $request, $eventId, $attachmentId): JsonResponse
    {
        $user = $this->authenticateUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $event = Event::findOrFail($eventId);
        $attachment = $event->attachments()->findOrFail($attachmentId);

        return $this->sendSuccess([
            'attachment' => $attachment
        ], 'Lấy tệp thành công!');
    }

    public function update(Request $request, $eventId, $attachmentId): JsonResponse
    {
        $user = $this->authenticateUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'file_name' => 'sometimes|string|max:255',
        ]);

        $event = Event::findOrFail($eventId);
        $attachment = $event->attachments()->findOrFail($attachmentId);

        if ($request->has('file_name')) {
            $attachment->file_name = $request->input('file_name');
            $attachment->save();
        }

        return response()->json([
            'data' => $attachment,
            'message' => 'Attachment updated successfully',
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, $attachmentId): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Tìm tệp đính kèm theo ID
            $attachment = Attachment::find($attachmentId);
            if (!$attachment) {
                return $this->sendError('Tệp đính kèm không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            // Xóa tệp vật lý từ storage
            $filePath = str_replace('/storage/', '', $attachment->file_url);
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            } else {
                Log::warning("Tệp không tồn tại trong storage: {$filePath}");
            }

            // Xóa bản ghi trong cơ sở dữ liệu
            $attachment->delete();

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'message' => 'Tệp đính kèm đã được xóa thành công.',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Ghi log lỗi để debug
            Log::error('Lỗi khi xóa tệp đính kèm:', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            // Trả về phản hồi lỗi chung
            return $this->sendError('Đã xảy ra lỗi khi xóa tệp đính kèm.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
