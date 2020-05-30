CREATE TABLE "files" (
     "id"	INTEGER PRIMARY KEY AUTOINCREMENT,
     "name"	TEXT NOT NULL UNIQUE,
     "created_at"	TEXT NOT NULL,
     "updated_at"	TEXT NOT NULL
)