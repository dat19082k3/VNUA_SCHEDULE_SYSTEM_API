# Model Relationships Documentation

This document describes the relationships between models in the VNUA Schedule System API.

## User Model

### Relationships

- **primaryDepartment**: One-to-many (inverse) - A user belongs to one primary department
  ```php
  public function primaryDepartment()
  {
      return $this->belongsTo(Department::class, 'primary_department_id');
  }
  ```

- **departments**: Many-to-many - A user can belong to multiple departments
  ```php
  public function departments()
  {
      return $this->belongsToMany(Department::class, 'user_departments')
                  ->withTimestamps();
  }
  ```

- **markedEvents**: Many-to-many - Events that a user has marked (interested in)
  ```php
  public function markedEvents()
  {
      return $this->belongsToMany(Event::class, 'user_events')
                  ->withPivot('is_marked', 'is_viewed')
                  ->withTimestamps();
  }
  ```

- **uploadedAttachments**: One-to-many - Attachments uploaded by the user
  ```php
  public function uploadedAttachments()
  {
      return $this->hasMany(Attachment::class, 'uploader_id');
  }
  ```

- **createdEvents**: One-to-many - Events created by the user
  ```php
  public function createdEvents()
  {
      return $this->hasMany(Event::class, 'creator_id');
  }
  ```

### Helper Methods

- `markEvent($eventId)`: Mark an event as interested
- `unmarkEvent($eventId)`: Remove mark from an event
- `markEventAsViewed($eventId)`: Mark an event as viewed
- `hasMarkedEvent($eventId)`: Check if user has marked an event
- `hasViewedEvent($eventId)`: Check if user has viewed an event

## Department Model

### Relationships

- **events**: One-to-many - Events belonging to this department
  ```php
  public function events()
  {
      return $this->hasMany(Event::class);
  }
  ```

- **users**: Many-to-many - Users belonging to this department
  ```php
  public function users()
  {
      return $this->belongsToMany(User::class, 'user_departments')
                  ->withTimestamps();
  }
  ```

- **primaryUsers**: One-to-many - Users who have this department as their primary department
  ```php
  public function primaryUsers()
  {
      return $this->hasMany(User::class, 'primary_department_id');
  }
  ```

- **preparedEvents**: Many-to-many - Events that this department prepares
  ```php
  public function preparedEvents()
  {
      return $this->belongsToMany(Event::class, 'event_preparers', 'department_id', 'event_id')
                  ->withTimestamps();
  }
  ```

## Event Model

### Relationships

- **histories**: One-to-many - History entries for this event
  ```php
  public function histories()
  {
      return $this->hasMany(EventHistory::class);
  }
  ```

- **locations**: Many-to-many - Locations for this event
  ```php
  public function locations()
  {
      return $this->belongsToMany(Location::class, 'event_locations');
  }
  ```

- **preparers**: Many-to-many - Departments preparing this event
  ```php
  public function preparers()
  {
      return $this->belongsToMany(Department::class, 'event_preparers', 'event_id', 'department_id');
  }
  ```

- **attachments**: Many-to-many - Files attached to this event
  ```php
  public function attachments()
  {
      return $this->belongsToMany(Attachment::class, 'event_attachments')
          ->withPivot('added_at')
          ->withTimestamps();
  }
  ```

- **creator**: One-to-many (inverse) - User who created the event
  ```php
  public function creator()
  {
      return $this->belongsTo(User::class, 'creator_id');
  }
  ```

- **markedByUsers**: Many-to-many - Users who marked this event
  ```php
  public function markedByUsers()
  {
      return $this->belongsToMany(User::class, 'user_events')
                  ->withPivot('is_marked', 'is_viewed')
                  ->withTimestamps();
  }
  ```

### Participants JSON Structure

The `participants` field stores a JSON array of participants with their type and ID:

```json
[
  {"type": "user", "id": 1},
  {"type": "department", "id": 2}
]
```

### Helper Methods

- `addParticipant($type, $id)`: Add a participant (user or department) to the event
- `removeParticipant($type, $id)`: Remove a participant from the event
- `hasParticipant($type, $id)`: Check if a specific entity is a participant
- `getParticipantsByType($type)`: Get all participant IDs of a specific type

## Database Tables

- **users**: Stores user information
- **departments**: Stores department information
- **events**: Stores event information
- **user_departments**: Junction table for user-department many-to-many relationship
- **user_events**: Junction table for user-event many-to-many relationship with additional pivot data
- **event_preparers**: Junction table for event-department preparation relationship
- **event_locations**: Junction table for event-location relationship
- **event_attachments**: Junction table for event-attachment relationship
