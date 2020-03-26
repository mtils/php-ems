CREATE TABLE "tokens" (
    "id"	INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id"	INTEGER NOT NULL,
    "token_type"	INTEGER NOT NULL DEFAULT 1,
    "token"	TEXT NOT NULL UNIQUE,
    "expires_at"	TEXT,
    "created_at"	TEXT NOT NULL,
    "updated_at"	TEXT NOT NULL,
    FOREIGN KEY("user_id") REFERENCES "users"("id")
)