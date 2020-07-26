CREATE TABLE "projects" (
    "id"	INTEGER PRIMARY KEY AUTOINCREMENT,
    "name"	TEXT NOT NULL,
    "type_id"   INTEGER NOT NULL,
    "owner_id"   INTEGER NOT NULL,
    "created_at"	TEXT NOT NULL,
    "updated_at"	TEXT NOT NULL,
    FOREIGN KEY("owner_id") REFERENCES "users"("id"),
    FOREIGN KEY("type_id") REFERENCES "project_types"("id")
)