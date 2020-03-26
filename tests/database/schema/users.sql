CREATE TABLE "users" (
     "id"           INTEGER PRIMARY KEY AUTOINCREMENT,
     "email"        TEXT NOT NULL UNIQUE,
     "password"     TEXT NOT NULL,
     "web"          TEXT,
     "contact_id"   INTEGER,
     "created_at"   TEXT NOT NULL,
     "updated_at"   TEXT NOT NULL,
     FOREIGN KEY("contact_id") REFERENCES "contacts"("id")
)