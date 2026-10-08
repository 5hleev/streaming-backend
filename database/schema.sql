CREATE TABLE users (
    id            BIGSERIAL PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(16)  NOT NULL DEFAULT 'viewer' CHECK (role IN ('viewer', 'admin')),
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE plans (
    id            BIGSERIAL PRIMARY KEY,
    name          VARCHAR(64) NOT NULL UNIQUE,
    price_cents   INTEGER     NOT NULL CHECK (price_cents >= 0),
    duration_days INTEGER     NOT NULL CHECK (duration_days > 0)
);

CREATE TABLE subscriptions (
    id        BIGSERIAL PRIMARY KEY,
    user_id   BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    plan_id   BIGINT      NOT NULL REFERENCES plans(id),
    starts_at TIMESTAMPTZ NOT NULL,
    ends_at   TIMESTAMPTZ NOT NULL,
    CHECK (ends_at > starts_at)
);
CREATE INDEX idx_subscriptions_user ON subscriptions(user_id, ends_at);

CREATE TABLE contents (
    id            BIGSERIAL PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    description   TEXT         NOT NULL DEFAULT '',
    genre         VARCHAR(64)  NOT NULL,
    release_year  SMALLINT     NOT NULL,
    is_free       BOOLEAN      NOT NULL DEFAULT FALSE,
    status        VARCHAR(16)  NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'processing', 'published', 'failed')),
    source_path   VARCHAR(512),
    hls_path      VARCHAR(512),
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);
CREATE INDEX idx_contents_genre ON contents(genre);
CREATE INDEX idx_contents_title ON contents(lower(title));

CREATE TABLE transcode_jobs (
    id          BIGSERIAL PRIMARY KEY,
    content_id  BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    status      VARCHAR(16) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'running', 'done', 'failed')),
    error       TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    finished_at TIMESTAMPTZ
);
CREATE INDEX idx_jobs_status ON transcode_jobs(status, id);

CREATE TABLE watch_progress (
    user_id          BIGINT      NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content_id       BIGINT      NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    position_seconds INTEGER     NOT NULL CHECK (position_seconds >= 0),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, content_id)
);

INSERT INTO plans (name, price_cents, duration_days) VALUES
    ('Basic', 29900, 30),
    ('Premium', 49900, 30);
