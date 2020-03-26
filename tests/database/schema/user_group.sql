CREATE TABLE "user_group" (
    "user_id"	INTEGER,
    "group_id"	INTEGER,
    FOREIGN KEY("group_id") REFERENCES "groups"("id"),
    FOREIGN KEY("user_id") REFERENCES "users"("id")
)