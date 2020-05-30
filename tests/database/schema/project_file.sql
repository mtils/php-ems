CREATE TABLE "project_file" (
    "project_id"	INTEGER,
    "file_id"	    INTEGER,
    FOREIGN KEY("project_id") REFERENCES "projects"("id"),
    FOREIGN KEY("file_id") REFERENCES "files"("id")
)