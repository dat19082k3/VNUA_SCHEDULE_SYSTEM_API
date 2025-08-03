<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location_id',
        'start_time',
        'end_time',
        'host_id',
        'participants',
        'status',
        'reminder_type',
        'reminder_time',
        'creator_id',
        'preparer_id'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'reminder_time' => 'datetime',
        'reminder_type' => 'string',
        'participants' => 'json',
    ];

    public function histories()
    {
        return $this->hasMany(EventHistory::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'event_locations');
    }

    public function preparers()
    {
        return $this->belongsToMany(Department::class, 'event_preparers', 'event_id', 'department_id');
    }

    public function attachments()
    {
        return $this->belongsToMany(Attachment::class, 'event_attachments')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    // Mối quan hệ với người tạo sự kiện
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // Mối quan hệ với người chủ trì sự kiện
    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * Relationship với người dùng đã đánh dấu/xem sự kiện
     */
    public function markedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_events')
                    ->withPivot('is_marked', 'is_viewed')
                    ->withTimestamps();
    }

    /**
     * Thêm người tham gia vào sự kiện
     * @param string $type 'user' hoặc 'department'
     * @param int $id ID của người dùng hoặc phòng ban
     * @return bool
     */
    public function addParticipant(string $type, int $id): bool
    {
        $participants = $this->participants ?? [];

        // Kiểm tra xem đã có trong danh sách chưa
        foreach ($participants as $participant) {
            if ($participant['type'] === $type && $participant['id'] === $id) {
                return true; // Đã có trong danh sách
            }
        }

        // Thêm mới vào danh sách
        $participants[] = [
            'type' => $type,
            'id' => $id
        ];

        $this->attributes['participants'] = json_encode($participants);
        return $this->save();
    }

    /**
     * Xóa người tham gia khỏi sự kiện
     * @param string $type 'user' hoặc 'department'
     * @param int $id ID của người dùng hoặc phòng ban
     * @return bool
     */
    public function removeParticipant(string $type, int $id): bool
    {
        $participants = $this->participants ?? [];
        $newParticipants = [];

        foreach ($participants as $participant) {
            if (!($participant['type'] === $type && $participant['id'] === $id)) {
                $newParticipants[] = $participant;
            }
        }

        $this->attributes['participants'] = json_encode($newParticipants);
        return $this->save();
    }

    /**
     * Kiểm tra xem một người dùng/phòng ban có tham gia sự kiện không
     * @param string $type 'user' hoặc 'department'
     * @param int $id ID của người dùng hoặc phòng ban
     * @return bool
     */
    public function hasParticipant(string $type, int $id): bool
    {
        $participants = $this->participants ?? [];

        foreach ($participants as $participant) {
            if ($participant['type'] === $type && $participant['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lấy danh sách người tham gia theo loại
     * @param string $type 'user' hoặc 'department'
     * @return array
     */
    public function getParticipantsByType(string $type): array
    {
        $participants = $this->participants ?? [];
        $result = [];

        foreach ($participants as $participant) {
            if ($participant['type'] === $type) {
                $result[] = $participant['id'];
            }
        }

        return $result;
    }
}
