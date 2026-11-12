-- btree_gist pozwala połączyć w jednym ograniczeniu EXCLUDE równość (forklift_id)
-- z nakładaniem się przedziałów czasu (tstzrange). Pilnuje tego, by rezerwacje się nie nakładały.
CREATE EXTENSION IF NOT EXISTS btree_gist;
