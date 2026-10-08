-- LifeCycleTrack / Supabase PostgreSQL schema
-- Run this once in Supabase SQL Editor.

CREATE TABLE IF NOT EXISTS assets (
    id BIGSERIAL PRIMARY KEY,
    asset_type TEXT NOT NULL CHECK (asset_type IN ('facility', 'equipment')),
    name TEXT NOT NULL,
    brand TEXT,
    model TEXT,
    category TEXT,
    location TEXT NOT NULL,
    date_acquired DATE,
    condition TEXT NOT NULL DEFAULT 'Good' CHECK (condition IN ('Good', 'Fair', 'Poor')),
    details_name TEXT,
    remarks TEXT,
    image_url TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_assets_type ON assets(asset_type);
CREATE INDEX IF NOT EXISTS idx_assets_condition ON assets(condition);
CREATE INDEX IF NOT EXISTS idx_assets_location ON assets(location);

CREATE TABLE IF NOT EXISTS condition_assessments (
    id BIGSERIAL PRIMARY KEY,
    asset_id BIGINT NOT NULL REFERENCES assets(id) ON DELETE CASCADE,
    condition TEXT NOT NULL CHECK (condition IN ('Good', 'Fair', 'Poor')),
    notes TEXT,
    assessed_by TEXT,
    assessed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_condition_assessments_asset ON condition_assessments(asset_id);

CREATE TABLE IF NOT EXISTS activity_log (
    id BIGSERIAL PRIMARY KEY,
    asset_id BIGINT REFERENCES assets(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    details TEXT,
    actor TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_activity_log_created ON activity_log(created_at DESC);

-- Optional starter records. Remove this section if you already have data.
-- INSERT INTO assets (asset_type,name,location,condition,date_acquired,category)
-- VALUES
-- ('facility','Reception Hall','Captain''s Office','Good','2026-01-10','Facility'),
-- ('facility','DayCare','Captain''s Office','Good','2026-01-11','Facility'),
-- ('equipment','Printer','Secretary''s Office','Good','2026-02-01','Equipment'),
-- ('equipment','Airconditioner','Secretary''s Office','Good','2026-02-02','Equipment');
