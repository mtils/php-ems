CREATE TABLE "contacts" (
     "id"           INTEGER PRIMARY KEY AUTOINCREMENT,
     "first_name"   TEXT,
     "last_name"    TEXT,
     "company"      TEXT,
     "address"      TEXT,
     "city"         TEXT,
     "county"       TEXT,
     "postal"       TEXT,
     "phone1"       TEXT,
     "phone2"       TEXT,
     "created_at"   TEXT NOT NULL,
     "updated_at"   TEXT NOT NULL
)