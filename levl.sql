--
-- PostgreSQL database dump
--

\restrict xW1RxwGt64QKjyGORJgwEuPNO7KcgtdlAfuwXNCdNRQCtuU5FzkXlPtlJjM1Wfo

-- Dumped from database version 18.1 (Homebrew)
-- Dumped by pg_dump version 18.1 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid,
    ip_address character varying(255),
    browser character varying(255),
    browser_version character varying(255),
    platform character varying(255),
    device character varying(255),
    device_type character varying(255)
);


ALTER TABLE public.activity_log OWNER TO darrielmarkerizal;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activity_log_id_seq OWNER TO darrielmarkerizal;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: announcements; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.announcements (
    id bigint NOT NULL,
    author_id bigint NOT NULL,
    course_id bigint,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    target_type character varying(255) DEFAULT 'all'::character varying NOT NULL,
    target_value character varying(255),
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    published_at timestamp(0) without time zone,
    scheduled_at timestamp(0) without time zone,
    views_count integer DEFAULT 0 NOT NULL,
    deleted_at timestamp(0) without time zone,
    deleted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    slug character varying(255) NOT NULL,
    CONSTRAINT announcements_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying])::text[]))),
    CONSTRAINT announcements_status_check CHECK (((status)::text = ANY (ARRAY['draft'::text, 'submitted'::text, 'in_review'::text, 'approved'::text, 'rejected'::text, 'scheduled'::text, 'published'::text, 'archived'::text]))),
    CONSTRAINT announcements_target_type_check CHECK (((target_type)::text = ANY ((ARRAY['all'::character varying, 'role'::character varying, 'course'::character varying])::text[])))
);


ALTER TABLE public.announcements OWNER TO darrielmarkerizal;

--
-- Name: announcements_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.announcements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.announcements_id_seq OWNER TO darrielmarkerizal;

--
-- Name: announcements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.announcements_id_seq OWNED BY public.announcements.id;


--
-- Name: answers; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.answers (
    id bigint NOT NULL,
    submission_id bigint NOT NULL,
    question_id bigint NOT NULL,
    content text,
    selected_options json,
    file_paths json,
    score numeric(8,2),
    is_auto_graded boolean DEFAULT false NOT NULL,
    feedback text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    files_expired_at timestamp(0) without time zone,
    file_metadata json
);


ALTER TABLE public.answers OWNER TO darrielmarkerizal;

--
-- Name: answers_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.answers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.answers_id_seq OWNER TO darrielmarkerizal;

--
-- Name: answers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.answers_id_seq OWNED BY public.answers.id;


--
-- Name: appeals; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.appeals (
    id bigint NOT NULL,
    submission_id bigint NOT NULL,
    student_id bigint NOT NULL,
    reviewer_id bigint,
    reason text NOT NULL,
    supporting_documents json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    decision_reason text,
    submitted_at timestamp(0) without time zone,
    decided_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT appeals_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'denied'::character varying])::text[])))
);


ALTER TABLE public.appeals OWNER TO darrielmarkerizal;

--
-- Name: appeals_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.appeals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.appeals_id_seq OWNER TO darrielmarkerizal;

--
-- Name: appeals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.appeals_id_seq OWNED BY public.appeals.id;


--
-- Name: assignment_prerequisites; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.assignment_prerequisites (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    prerequisite_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.assignment_prerequisites OWNER TO darrielmarkerizal;

--
-- Name: assignment_prerequisites_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.assignment_prerequisites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assignment_prerequisites_id_seq OWNER TO darrielmarkerizal;

--
-- Name: assignment_prerequisites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.assignment_prerequisites_id_seq OWNED BY public.assignment_prerequisites.id;


--
-- Name: assignment_questions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.assignment_questions (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    type character varying(30) NOT NULL,
    content text NOT NULL,
    options json,
    answer_key json,
    weight numeric(8,2) DEFAULT '1'::numeric NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    max_score numeric(8,2) DEFAULT '100'::numeric NOT NULL,
    max_file_size integer,
    allowed_file_types json,
    allow_multiple_files boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.assignment_questions OWNER TO darrielmarkerizal;

--
-- Name: assignment_questions_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.assignment_questions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assignment_questions_id_seq OWNER TO darrielmarkerizal;

--
-- Name: assignment_questions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.assignment_questions_id_seq OWNED BY public.assignment_questions.id;


--
-- Name: assignments; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.assignments (
    id bigint NOT NULL,
    lesson_id bigint,
    created_by bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    submission_type character varying(255) DEFAULT 'text'::character varying NOT NULL,
    max_score integer DEFAULT 100 NOT NULL,
    available_from timestamp(0) without time zone,
    deadline_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    allow_resubmit boolean,
    late_penalty_percent integer,
    assignable_type character varying(255),
    assignable_id bigint,
    tolerance_minutes integer DEFAULT 0 NOT NULL,
    max_attempts integer,
    cooldown_minutes integer DEFAULT 0 NOT NULL,
    retake_enabled boolean DEFAULT false NOT NULL,
    review_mode character varying(20) DEFAULT 'immediate'::character varying NOT NULL,
    randomization_type character varying(20) DEFAULT 'static'::character varying NOT NULL,
    question_bank_count integer,
    time_limit_minutes integer,
    CONSTRAINT assignments_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying, 'archived'::character varying])::text[]))),
    CONSTRAINT assignments_submission_type_check CHECK (((submission_type)::text = ANY ((ARRAY['text'::character varying, 'file'::character varying, 'mixed'::character varying])::text[])))
);


ALTER TABLE public.assignments OWNER TO darrielmarkerizal;

--
-- Name: COLUMN assignments.time_limit_minutes; Type: COMMENT; Schema: public; Owner: darrielmarkerizal
--

COMMENT ON COLUMN public.assignments.time_limit_minutes IS 'Optional time limit per attempt (in minutes)';


--
-- Name: assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assignments_id_seq OWNER TO darrielmarkerizal;

--
-- Name: assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.assignments_id_seq OWNED BY public.assignments.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    event character varying(255) DEFAULT 'system'::character varying NOT NULL,
    target_type character varying(255),
    target_id bigint,
    actor_type character varying(255),
    actor_id bigint,
    user_id bigint,
    properties json,
    logged_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    action character varying(255),
    subject_type character varying(255),
    subject_id bigint,
    context json,
    CONSTRAINT audit_logs_event_check CHECK (((event)::text = ANY ((ARRAY['create'::character varying, 'update'::character varying, 'delete'::character varying, 'login'::character varying, 'logout'::character varying, 'assign'::character varying, 'revoke'::character varying, 'export'::character varying, 'import'::character varying, 'system'::character varying])::text[])))
);


ALTER TABLE public.audit_logs OWNER TO darrielmarkerizal;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO darrielmarkerizal;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: audits; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.audits (
    id bigint NOT NULL,
    action character varying(255) DEFAULT 'system'::character varying NOT NULL,
    actor_type character varying(255),
    actor_id bigint,
    user_id bigint,
    target_table character varying(100),
    target_type character varying(255),
    target_id bigint,
    module character varying(100),
    context character varying(255) DEFAULT 'application'::character varying NOT NULL,
    ip_address character varying(50),
    user_agent character varying(255),
    meta json,
    properties json,
    logged_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT audits_action_check CHECK (((action)::text = ANY ((ARRAY['create'::character varying, 'update'::character varying, 'delete'::character varying, 'login'::character varying, 'logout'::character varying, 'assign'::character varying, 'revoke'::character varying, 'export'::character varying, 'import'::character varying, 'access'::character varying, 'error'::character varying, 'system'::character varying])::text[]))),
    CONSTRAINT audits_context_check CHECK (((context)::text = ANY ((ARRAY['system'::character varying, 'application'::character varying])::text[])))
);


ALTER TABLE public.audits OWNER TO darrielmarkerizal;

--
-- Name: audits_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audits_id_seq OWNER TO darrielmarkerizal;

--
-- Name: audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.audits_id_seq OWNED BY public.audits.id;


--
-- Name: badges; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.badges (
    id bigint NOT NULL,
    code character varying(100) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    type character varying(255) DEFAULT 'achievement'::character varying NOT NULL,
    threshold integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT badges_type_check CHECK (((type)::text = ANY ((ARRAY['achievement'::character varying, 'milestone'::character varying, 'completion'::character varying])::text[])))
);


ALTER TABLE public.badges OWNER TO darrielmarkerizal;

--
-- Name: badges_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.badges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.badges_id_seq OWNER TO darrielmarkerizal;

--
-- Name: badges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.badges_id_seq OWNED BY public.badges.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO darrielmarkerizal;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO darrielmarkerizal;

--
-- Name: categories; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    value character varying(100) NOT NULL,
    description character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT categories_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.categories OWNER TO darrielmarkerizal;

--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categories_id_seq OWNER TO darrielmarkerizal;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: certificates; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.certificates (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    course_id bigint NOT NULL,
    certificate_number character varying(100) NOT NULL,
    issued_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expired_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT certificates_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'revoked'::character varying, 'expired'::character varying])::text[])))
);


ALTER TABLE public.certificates OWNER TO darrielmarkerizal;

--
-- Name: certificates_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.certificates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.certificates_id_seq OWNER TO darrielmarkerizal;

--
-- Name: certificates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.certificates_id_seq OWNED BY public.certificates.id;


--
-- Name: challenges; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.challenges (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    type character varying(255) DEFAULT 'special'::character varying NOT NULL,
    points_reward integer DEFAULT 50 NOT NULL,
    badge_id bigint,
    start_at timestamp(0) without time zone,
    end_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    criteria json,
    target_count integer DEFAULT 1 NOT NULL,
    CONSTRAINT challenges_type_check CHECK (((type)::text = ANY ((ARRAY['daily'::character varying, 'weekly'::character varying, 'special'::character varying])::text[])))
);


ALTER TABLE public.challenges OWNER TO darrielmarkerizal;

--
-- Name: challenges_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.challenges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.challenges_id_seq OWNER TO darrielmarkerizal;

--
-- Name: challenges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.challenges_id_seq OWNED BY public.challenges.id;


--
-- Name: content_categories; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.content_categories (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.content_categories OWNER TO darrielmarkerizal;

--
-- Name: content_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.content_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.content_categories_id_seq OWNER TO darrielmarkerizal;

--
-- Name: content_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.content_categories_id_seq OWNED BY public.content_categories.id;


--
-- Name: content_reads; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.content_reads (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    readable_type character varying(255) NOT NULL,
    readable_id bigint NOT NULL,
    read_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.content_reads OWNER TO darrielmarkerizal;

--
-- Name: content_reads_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.content_reads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.content_reads_id_seq OWNER TO darrielmarkerizal;

--
-- Name: content_reads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.content_reads_id_seq OWNED BY public.content_reads.id;


--
-- Name: content_revisions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.content_revisions (
    id bigint NOT NULL,
    content_type character varying(255) NOT NULL,
    content_id bigint NOT NULL,
    editor_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    revision_note text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.content_revisions OWNER TO darrielmarkerizal;

--
-- Name: content_revisions_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.content_revisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.content_revisions_id_seq OWNER TO darrielmarkerizal;

--
-- Name: content_revisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.content_revisions_id_seq OWNED BY public.content_revisions.id;


--
-- Name: content_workflow_history; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.content_workflow_history (
    id bigint NOT NULL,
    content_type character varying(255) NOT NULL,
    content_id bigint NOT NULL,
    from_state character varying(50) NOT NULL,
    to_state character varying(50) NOT NULL,
    user_id bigint NOT NULL,
    note text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.content_workflow_history OWNER TO darrielmarkerizal;

--
-- Name: content_workflow_history_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.content_workflow_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.content_workflow_history_id_seq OWNER TO darrielmarkerizal;

--
-- Name: content_workflow_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.content_workflow_history_id_seq OWNED BY public.content_workflow_history.id;


--
-- Name: course_admins; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.course_admins (
    id bigint NOT NULL,
    course_id bigint NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.course_admins OWNER TO darrielmarkerizal;

--
-- Name: course_admins_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.course_admins_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.course_admins_id_seq OWNER TO darrielmarkerizal;

--
-- Name: course_admins_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.course_admins_id_seq OWNED BY public.course_admins.id;


--
-- Name: course_outcomes; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.course_outcomes (
    id bigint NOT NULL,
    course_id bigint NOT NULL,
    outcome_text text NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.course_outcomes OWNER TO darrielmarkerizal;

--
-- Name: course_outcomes_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.course_outcomes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.course_outcomes_id_seq OWNER TO darrielmarkerizal;

--
-- Name: course_outcomes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.course_outcomes_id_seq OWNED BY public.course_outcomes.id;


--
-- Name: course_progress; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.course_progress (
    id bigint NOT NULL,
    enrollment_id bigint NOT NULL,
    status character varying(255) DEFAULT 'not_started'::character varying NOT NULL,
    progress_percent double precision DEFAULT '0'::double precision NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT course_progress_status_check CHECK (((status)::text = ANY ((ARRAY['not_started'::character varying, 'in_progress'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.course_progress OWNER TO darrielmarkerizal;

--
-- Name: course_progress_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.course_progress_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.course_progress_id_seq OWNER TO darrielmarkerizal;

--
-- Name: course_progress_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.course_progress_id_seq OWNED BY public.course_progress.id;


--
-- Name: course_tag_pivot; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.course_tag_pivot (
    id bigint NOT NULL,
    course_id bigint NOT NULL,
    tag_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.course_tag_pivot OWNER TO darrielmarkerizal;

--
-- Name: course_tag_pivot_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.course_tag_pivot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.course_tag_pivot_id_seq OWNER TO darrielmarkerizal;

--
-- Name: course_tag_pivot_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.course_tag_pivot_id_seq OWNED BY public.course_tag_pivot.id;


--
-- Name: courses; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.courses (
    id bigint NOT NULL,
    code character varying(50) NOT NULL,
    slug character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    short_desc text,
    type character varying(255) DEFAULT 'okupasi'::character varying NOT NULL,
    level_tag character varying(255) DEFAULT 'dasar'::character varying NOT NULL,
    tags_json json,
    enrollment_type character varying(255) DEFAULT 'auto_accept'::character varying NOT NULL,
    progression_mode character varying(255) DEFAULT 'sequential'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    published_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    instructor_id bigint,
    category_id bigint,
    prereq_json json,
    prereq_text text,
    deleted_by bigint,
    enrollment_key_hash character varying(255),
    CONSTRAINT courses_enrollment_type_check CHECK (((enrollment_type)::text = ANY ((ARRAY['auto_accept'::character varying, 'key_based'::character varying, 'approval'::character varying])::text[]))),
    CONSTRAINT courses_level_tag_check CHECK (((level_tag)::text = ANY ((ARRAY['dasar'::character varying, 'menengah'::character varying, 'mahir'::character varying])::text[]))),
    CONSTRAINT courses_progression_mode_check CHECK (((progression_mode)::text = ANY ((ARRAY['sequential'::character varying, 'free'::character varying])::text[]))),
    CONSTRAINT courses_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying, 'archived'::character varying])::text[]))),
    CONSTRAINT courses_type_check CHECK (((type)::text = ANY ((ARRAY['okupasi'::character varying, 'kluster'::character varying])::text[])))
);


ALTER TABLE public.courses OWNER TO darrielmarkerizal;

--
-- Name: courses_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.courses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.courses_id_seq OWNER TO darrielmarkerizal;

--
-- Name: courses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.courses_id_seq OWNED BY public.courses.id;


--
-- Name: enrollments; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.enrollments (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    course_id bigint NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    enrolled_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT enrollments_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'active'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.enrollments OWNER TO darrielmarkerizal;

--
-- Name: enrollments_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.enrollments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.enrollments_id_seq OWNER TO darrielmarkerizal;

--
-- Name: enrollments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.enrollments_id_seq OWNED BY public.enrollments.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO darrielmarkerizal;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO darrielmarkerizal;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: forum_statistics; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.forum_statistics (
    id bigint NOT NULL,
    scheme_id bigint NOT NULL,
    user_id bigint,
    threads_count integer DEFAULT 0 NOT NULL,
    replies_count integer DEFAULT 0 NOT NULL,
    views_count integer DEFAULT 0 NOT NULL,
    avg_response_time_minutes integer,
    response_rate numeric(5,2),
    period_start date NOT NULL,
    period_end date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.forum_statistics OWNER TO darrielmarkerizal;

--
-- Name: forum_statistics_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.forum_statistics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.forum_statistics_id_seq OWNER TO darrielmarkerizal;

--
-- Name: forum_statistics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.forum_statistics_id_seq OWNED BY public.forum_statistics.id;


--
-- Name: gamification_milestones; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.gamification_milestones (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    xp_required integer NOT NULL,
    level_required integer NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.gamification_milestones OWNER TO darrielmarkerizal;

--
-- Name: gamification_milestones_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.gamification_milestones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.gamification_milestones_id_seq OWNER TO darrielmarkerizal;

--
-- Name: gamification_milestones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.gamification_milestones_id_seq OWNED BY public.gamification_milestones.id;


--
-- Name: grade_reviews; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.grade_reviews (
    id bigint NOT NULL,
    grade_id bigint NOT NULL,
    requested_by bigint NOT NULL,
    reason text NOT NULL,
    response text,
    reviewed_by bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT grade_reviews_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.grade_reviews OWNER TO darrielmarkerizal;

--
-- Name: grade_reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.grade_reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.grade_reviews_id_seq OWNER TO darrielmarkerizal;

--
-- Name: grade_reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.grade_reviews_id_seq OWNED BY public.grade_reviews.id;


--
-- Name: grades; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.grades (
    id bigint NOT NULL,
    source_type character varying(255) NOT NULL,
    source_id bigint NOT NULL,
    user_id bigint NOT NULL,
    graded_by bigint,
    score numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    max_score numeric(8,2) DEFAULT '100'::numeric NOT NULL,
    feedback text,
    status character varying(255) DEFAULT 'graded'::character varying NOT NULL,
    graded_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    submission_id bigint,
    original_score numeric(8,2),
    is_override boolean DEFAULT false NOT NULL,
    override_reason text,
    is_draft boolean DEFAULT false NOT NULL,
    released_at timestamp(0) without time zone,
    CONSTRAINT grades_source_type_check CHECK (((source_type)::text = ANY ((ARRAY['assignment'::character varying, 'attempt'::character varying])::text[]))),
    CONSTRAINT grades_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'graded'::character varying, 'reviewed'::character varying])::text[])))
);


ALTER TABLE public.grades OWNER TO darrielmarkerizal;

--
-- Name: grades_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.grades_id_seq OWNER TO darrielmarkerizal;

--
-- Name: grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.grades_id_seq OWNED BY public.grades.id;


--
-- Name: grading_rubrics; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.grading_rubrics (
    id bigint NOT NULL,
    scope_type character varying(255) NOT NULL,
    scope_id bigint NOT NULL,
    criteria json NOT NULL,
    description text,
    max_score integer DEFAULT 10 NOT NULL,
    weight integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT grading_rubrics_scope_type_check CHECK (((scope_type)::text = ANY ((ARRAY['exercise'::character varying, 'assignment'::character varying])::text[])))
);


ALTER TABLE public.grading_rubrics OWNER TO darrielmarkerizal;

--
-- Name: grading_rubrics_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.grading_rubrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.grading_rubrics_id_seq OWNER TO darrielmarkerizal;

--
-- Name: grading_rubrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.grading_rubrics_id_seq OWNED BY public.grading_rubrics.id;


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO darrielmarkerizal;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO darrielmarkerizal;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: jwt_refresh_tokens; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.jwt_refresh_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    token character varying(255) NOT NULL,
    ip character varying(45),
    user_agent character varying(255),
    revoked_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    device_id character varying(64),
    replaced_by bigint,
    last_used_at timestamp(0) without time zone,
    idle_expires_at timestamp(0) without time zone,
    absolute_expires_at timestamp(0) without time zone
);


ALTER TABLE public.jwt_refresh_tokens OWNER TO darrielmarkerizal;

--
-- Name: jwt_refresh_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.jwt_refresh_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jwt_refresh_tokens_id_seq OWNER TO darrielmarkerizal;

--
-- Name: jwt_refresh_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.jwt_refresh_tokens_id_seq OWNED BY public.jwt_refresh_tokens.id;


--
-- Name: leaderboards; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.leaderboards (
    id bigint NOT NULL,
    course_id bigint,
    user_id bigint NOT NULL,
    rank integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.leaderboards OWNER TO darrielmarkerizal;

--
-- Name: leaderboards_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.leaderboards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.leaderboards_id_seq OWNER TO darrielmarkerizal;

--
-- Name: leaderboards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.leaderboards_id_seq OWNED BY public.leaderboards.id;


--
-- Name: learning_streaks; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.learning_streaks (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    activity_date date NOT NULL,
    xp_earned integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.learning_streaks OWNER TO darrielmarkerizal;

--
-- Name: learning_streaks_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.learning_streaks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.learning_streaks_id_seq OWNER TO darrielmarkerizal;

--
-- Name: learning_streaks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.learning_streaks_id_seq OWNED BY public.learning_streaks.id;


--
-- Name: lesson_blocks; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.lesson_blocks (
    id bigint NOT NULL,
    lesson_id bigint NOT NULL,
    block_type character varying(255) DEFAULT 'text'::character varying NOT NULL,
    content text,
    "order" integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    slug character varying(255) NOT NULL,
    CONSTRAINT lesson_blocks_block_type_check CHECK (((block_type)::text = ANY (ARRAY['text'::text, 'image'::text, 'file'::text, 'embed'::text, 'video'::text])))
);


ALTER TABLE public.lesson_blocks OWNER TO darrielmarkerizal;

--
-- Name: lesson_blocks_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.lesson_blocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lesson_blocks_id_seq OWNER TO darrielmarkerizal;

--
-- Name: lesson_blocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.lesson_blocks_id_seq OWNED BY public.lesson_blocks.id;


--
-- Name: lesson_progress; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.lesson_progress (
    id bigint NOT NULL,
    enrollment_id bigint NOT NULL,
    lesson_id bigint NOT NULL,
    status character varying(255) DEFAULT 'not_started'::character varying NOT NULL,
    progress_percent double precision DEFAULT '0'::double precision NOT NULL,
    attempt_count integer DEFAULT 0 NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT lesson_progress_status_check CHECK (((status)::text = ANY ((ARRAY['not_started'::character varying, 'in_progress'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.lesson_progress OWNER TO darrielmarkerizal;

--
-- Name: lesson_progress_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.lesson_progress_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lesson_progress_id_seq OWNER TO darrielmarkerizal;

--
-- Name: lesson_progress_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.lesson_progress_id_seq OWNED BY public.lesson_progress.id;


--
-- Name: lessons; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.lessons (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    slug character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    markdown_content text,
    content_type character varying(255) DEFAULT 'markdown'::character varying NOT NULL,
    content_url character varying(255),
    "order" integer DEFAULT 1 NOT NULL,
    duration_minutes integer DEFAULT 0 CONSTRAINT lessons_estimated_duration_not_null NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    published_at timestamp(0) without time zone,
    CONSTRAINT lessons_content_type_check CHECK (((content_type)::text = ANY ((ARRAY['markdown'::character varying, 'video'::character varying, 'link'::character varying])::text[]))),
    CONSTRAINT lessons_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying])::text[])))
);


ALTER TABLE public.lessons OWNER TO darrielmarkerizal;

--
-- Name: lessons_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.lessons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lessons_id_seq OWNER TO darrielmarkerizal;

--
-- Name: lessons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.lessons_id_seq OWNED BY public.lessons.id;


--
-- Name: levels; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.levels (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    course_id bigint,
    current_level integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.levels OWNER TO darrielmarkerizal;

--
-- Name: levels_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.levels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.levels_id_seq OWNER TO darrielmarkerizal;

--
-- Name: levels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.levels_id_seq OWNED BY public.levels.id;


--
-- Name: login_activities; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.login_activities (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    ip character varying(45),
    user_agent character varying(255),
    status character varying(255) NOT NULL,
    logged_in_at timestamp(0) without time zone,
    logged_out_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT login_activities_status_check CHECK (((status)::text = ANY ((ARRAY['success'::character varying, 'failed'::character varying])::text[])))
);


ALTER TABLE public.login_activities OWNER TO darrielmarkerizal;

--
-- Name: login_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.login_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.login_activities_id_seq OWNER TO darrielmarkerizal;

--
-- Name: login_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.login_activities_id_seq OWNED BY public.login_activities.id;


--
-- Name: master_data; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.master_data (
    id bigint NOT NULL,
    type character varying(50) NOT NULL,
    value character varying(100) NOT NULL,
    label character varying(255) NOT NULL,
    metadata json,
    is_system boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.master_data OWNER TO darrielmarkerizal;

--
-- Name: master_data_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.master_data_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.master_data_id_seq OWNER TO darrielmarkerizal;

--
-- Name: master_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.master_data_id_seq OWNED BY public.master_data.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.media (
    id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    uuid uuid,
    collection_name character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    mime_type character varying(255),
    disk character varying(255) NOT NULL,
    conversions_disk character varying(255),
    size bigint NOT NULL,
    manipulations json NOT NULL,
    custom_properties json NOT NULL,
    generated_conversions json NOT NULL,
    responsive_images json NOT NULL,
    order_column integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.media OWNER TO darrielmarkerizal;

--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.media_id_seq OWNER TO darrielmarkerizal;

--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO darrielmarkerizal;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO darrielmarkerizal;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO darrielmarkerizal;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO darrielmarkerizal;

--
-- Name: news; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.news (
    id bigint NOT NULL,
    author_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    excerpt text,
    content text NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    published_at timestamp(0) without time zone,
    scheduled_at timestamp(0) without time zone,
    views_count integer DEFAULT 0 NOT NULL,
    deleted_at timestamp(0) without time zone,
    deleted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT news_status_check CHECK (((status)::text = ANY (ARRAY['draft'::text, 'submitted'::text, 'in_review'::text, 'approved'::text, 'rejected'::text, 'scheduled'::text, 'published'::text, 'archived'::text])))
);


ALTER TABLE public.news OWNER TO darrielmarkerizal;

--
-- Name: news_category; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.news_category (
    news_id bigint NOT NULL,
    category_id bigint NOT NULL
);


ALTER TABLE public.news_category OWNER TO darrielmarkerizal;

--
-- Name: news_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.news_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.news_id_seq OWNER TO darrielmarkerizal;

--
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.news_id_seq OWNED BY public.news.id;


--
-- Name: notification_preferences; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.notification_preferences (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    category character varying(50) NOT NULL,
    channel character varying(50) NOT NULL,
    enabled boolean DEFAULT true NOT NULL,
    frequency character varying(50) DEFAULT 'immediate'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT notification_preferences_category_check CHECK (((category)::text = ANY (ARRAY['system'::text, 'assignment'::text, 'assessment'::text, 'grading'::text, 'gamification'::text, 'news'::text, 'custom'::text, 'course_completed'::text, 'course_updates'::text, 'assignments'::text, 'forum'::text, 'achievements'::text, 'enrollment'::text, 'forum_reply_to_thread'::text, 'forum_reply_to_reply'::text]))),
    CONSTRAINT notification_preferences_channel_check CHECK (((channel)::text = ANY (ARRAY['in_app'::text, 'email'::text, 'push'::text]))),
    CONSTRAINT notification_preferences_frequency_check CHECK (((frequency)::text = ANY (ARRAY['immediate'::text, 'daily'::text, 'weekly'::text, 'never'::text])))
);


ALTER TABLE public.notification_preferences OWNER TO darrielmarkerizal;

--
-- Name: notification_preferences_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.notification_preferences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_preferences_id_seq OWNER TO darrielmarkerizal;

--
-- Name: notification_preferences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.notification_preferences_id_seq OWNED BY public.notification_preferences.id;


--
-- Name: notification_templates; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.notification_templates (
    id bigint NOT NULL,
    code character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    body text NOT NULL,
    channel character varying(255) DEFAULT 'in_app'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT notification_templates_channel_check CHECK (((channel)::text = ANY ((ARRAY['in_app'::character varying, 'email'::character varying, 'push'::character varying])::text[])))
);


ALTER TABLE public.notification_templates OWNER TO darrielmarkerizal;

--
-- Name: notification_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.notification_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_templates_id_seq OWNER TO darrielmarkerizal;

--
-- Name: notification_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.notification_templates_id_seq OWNED BY public.notification_templates.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    type character varying(255) DEFAULT 'system'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    message text,
    data json,
    action_url character varying(255),
    channel character varying(255) DEFAULT 'in_app'::character varying NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    is_broadcast boolean DEFAULT false NOT NULL,
    scheduled_at timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT notifications_channel_check CHECK (((channel)::text = ANY ((ARRAY['in_app'::character varying, 'email'::character varying, 'push'::character varying])::text[]))),
    CONSTRAINT notifications_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying])::text[]))),
    CONSTRAINT notifications_type_check CHECK (((type)::text = ANY (ARRAY['system'::text, 'assignment'::text, 'assessment'::text, 'grading'::text, 'gamification'::text, 'news'::text, 'custom'::text, 'course_completed'::text, 'enrollment'::text, 'forum_reply_to_thread'::text, 'forum_reply_to_reply'::text, 'assignments'::text, 'forum'::text, 'achievements'::text, 'course_updates'::text, 'promotions'::text, ('schedule_reminder'::character varying)::text])))
);


ALTER TABLE public.notifications OWNER TO darrielmarkerizal;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notifications_id_seq OWNER TO darrielmarkerizal;

--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: otp_codes; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.otp_codes (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    channel character varying(255) NOT NULL,
    provider character varying(50),
    purpose character varying(255) NOT NULL,
    code character varying(20) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    consumed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    uuid uuid NOT NULL,
    meta json,
    CONSTRAINT otp_codes_channel_check CHECK (((channel)::text = 'email'::text)),
    CONSTRAINT otp_codes_purpose_check CHECK (((purpose)::text = ANY (ARRAY['register_verification'::text, 'password_reset'::text, 'email_change_verification'::text, 'two_factor_auth'::text, 'account_deletion'::text])))
);


ALTER TABLE public.otp_codes OWNER TO darrielmarkerizal;

--
-- Name: otp_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.otp_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.otp_codes_id_seq OWNER TO darrielmarkerizal;

--
-- Name: otp_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.otp_codes_id_seq OWNED BY public.otp_codes.id;


--
-- Name: overrides; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.overrides (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    student_id bigint NOT NULL,
    grantor_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    reason text NOT NULL,
    value json,
    granted_at timestamp(0) without time zone NOT NULL,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.overrides OWNER TO darrielmarkerizal;

--
-- Name: overrides_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.overrides_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.overrides_id_seq OWNER TO darrielmarkerizal;

--
-- Name: overrides_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.overrides_id_seq OWNED BY public.overrides.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.password_reset_tokens (
    email character varying(191) NOT NULL,
    token character varying(191) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO darrielmarkerizal;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO darrielmarkerizal;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO darrielmarkerizal;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: pinned_badges; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.pinned_badges (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    badge_id bigint NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.pinned_badges OWNER TO darrielmarkerizal;

--
-- Name: pinned_badges_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.pinned_badges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pinned_badges_id_seq OWNER TO darrielmarkerizal;

--
-- Name: pinned_badges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.pinned_badges_id_seq OWNED BY public.pinned_badges.id;


--
-- Name: points; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.points (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    source_type character varying(255) DEFAULT 'system'::character varying NOT NULL,
    source_id bigint,
    points integer DEFAULT 0 NOT NULL,
    reason character varying(255) DEFAULT 'completion'::character varying NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT points_reason_check CHECK (((reason)::text = ANY ((ARRAY['completion'::character varying, 'score'::character varying, 'bonus'::character varying, 'penalty'::character varying])::text[]))),
    CONSTRAINT points_source_type_check CHECK (((source_type)::text = ANY (ARRAY['lesson'::text, 'assignment'::text, 'attempt'::text, 'challenge'::text, 'system'::text])))
);


ALTER TABLE public.points OWNER TO darrielmarkerizal;

--
-- Name: points_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.points_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.points_id_seq OWNER TO darrielmarkerizal;

--
-- Name: points_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.points_id_seq OWNED BY public.points.id;


--
-- Name: profile_audit_logs; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.profile_audit_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    admin_id bigint,
    action character varying(50) NOT NULL,
    changes json,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.profile_audit_logs OWNER TO darrielmarkerizal;

--
-- Name: profile_audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.profile_audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.profile_audit_logs_id_seq OWNER TO darrielmarkerizal;

--
-- Name: profile_audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.profile_audit_logs_id_seq OWNED BY public.profile_audit_logs.id;


--
-- Name: profile_privacy_settings; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.profile_privacy_settings (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    profile_visibility character varying(255) DEFAULT 'public'::character varying NOT NULL,
    show_email boolean DEFAULT false NOT NULL,
    show_phone boolean DEFAULT false NOT NULL,
    show_activity_history boolean DEFAULT true NOT NULL,
    show_achievements boolean DEFAULT true NOT NULL,
    show_statistics boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT profile_privacy_settings_profile_visibility_check CHECK (((profile_visibility)::text = ANY ((ARRAY['public'::character varying, 'private'::character varying, 'friends_only'::character varying])::text[])))
);


ALTER TABLE public.profile_privacy_settings OWNER TO darrielmarkerizal;

--
-- Name: profile_privacy_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.profile_privacy_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.profile_privacy_settings_id_seq OWNER TO darrielmarkerizal;

--
-- Name: profile_privacy_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.profile_privacy_settings_id_seq OWNED BY public.profile_privacy_settings.id;


--
-- Name: reactions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.reactions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    reactable_type character varying(255) NOT NULL,
    reactable_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT reactions_type_check CHECK (((type)::text = ANY ((ARRAY['like'::character varying, 'helpful'::character varying, 'solved'::character varying])::text[])))
);


ALTER TABLE public.reactions OWNER TO darrielmarkerizal;

--
-- Name: reactions_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.reactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reactions_id_seq OWNER TO darrielmarkerizal;

--
-- Name: reactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.reactions_id_seq OWNED BY public.reactions.id;


--
-- Name: replies; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.replies (
    id bigint NOT NULL,
    thread_id bigint NOT NULL,
    parent_id bigint,
    author_id bigint NOT NULL,
    content text NOT NULL,
    depth integer DEFAULT 0 NOT NULL,
    is_accepted_answer boolean DEFAULT false NOT NULL,
    edited_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    deleted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.replies OWNER TO darrielmarkerizal;

--
-- Name: replies_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.replies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.replies_id_seq OWNER TO darrielmarkerizal;

--
-- Name: replies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.replies_id_seq OWNED BY public.replies.id;


--
-- Name: reports; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.reports (
    id bigint NOT NULL,
    type character varying(255) DEFAULT 'activity'::character varying NOT NULL,
    generated_by bigint,
    filters json,
    notes text,
    generated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reports_type_check CHECK (((type)::text = ANY ((ARRAY['activity'::character varying, 'assessment'::character varying, 'grading'::character varying, 'system'::character varying, 'custom'::character varying])::text[])))
);


ALTER TABLE public.reports OWNER TO darrielmarkerizal;

--
-- Name: reports_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reports_id_seq OWNER TO darrielmarkerizal;

--
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO darrielmarkerizal;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO darrielmarkerizal;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO darrielmarkerizal;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: search_history; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.search_history (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    query character varying(500) NOT NULL,
    filters json,
    results_count integer DEFAULT 0 NOT NULL,
    clicked_result_id bigint,
    clicked_result_type character varying(100),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.search_history OWNER TO darrielmarkerizal;

--
-- Name: search_history_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.search_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.search_history_id_seq OWNER TO darrielmarkerizal;

--
-- Name: search_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.search_history_id_seq OWNED BY public.search_history.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO darrielmarkerizal;

--
-- Name: social_accounts; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.social_accounts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    provider_name character varying(50) NOT NULL,
    provider_id character varying(191) NOT NULL,
    token text,
    refresh_token text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.social_accounts OWNER TO darrielmarkerizal;

--
-- Name: social_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.social_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.social_accounts_id_seq OWNER TO darrielmarkerizal;

--
-- Name: social_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.social_accounts_id_seq OWNED BY public.social_accounts.id;


--
-- Name: submission_files; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.submission_files (
    id bigint NOT NULL,
    submission_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.submission_files OWNER TO darrielmarkerizal;

--
-- Name: submission_files_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.submission_files_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.submission_files_id_seq OWNER TO darrielmarkerizal;

--
-- Name: submission_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.submission_files_id_seq OWNED BY public.submission_files.id;


--
-- Name: submissions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.submissions (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    user_id bigint NOT NULL,
    enrollment_id bigint,
    answer_text text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    submitted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    attempt_number integer DEFAULT 1 NOT NULL,
    is_late boolean DEFAULT false NOT NULL,
    is_resubmission boolean DEFAULT false NOT NULL,
    previous_submission_id bigint,
    score numeric(8,2),
    question_set json,
    state character varying(30),
    started_at timestamp(0) without time zone,
    time_expired_at timestamp(0) without time zone,
    auto_submitted_on_timeout boolean DEFAULT false NOT NULL,
    CONSTRAINT submissions_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'submitted'::character varying, 'graded'::character varying, 'late'::character varying])::text[])))
);


ALTER TABLE public.submissions OWNER TO darrielmarkerizal;

--
-- Name: COLUMN submissions.started_at; Type: COMMENT; Schema: public; Owner: darrielmarkerizal
--

COMMENT ON COLUMN public.submissions.started_at IS 'When the submission attempt was started';


--
-- Name: COLUMN submissions.time_expired_at; Type: COMMENT; Schema: public; Owner: darrielmarkerizal
--

COMMENT ON COLUMN public.submissions.time_expired_at IS 'When the submission time limit expired';


--
-- Name: COLUMN submissions.auto_submitted_on_timeout; Type: COMMENT; Schema: public; Owner: darrielmarkerizal
--

COMMENT ON COLUMN public.submissions.auto_submitted_on_timeout IS 'Whether submission was auto-submitted when time limit expired';


--
-- Name: submissions_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.submissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.submissions_id_seq OWNER TO darrielmarkerizal;

--
-- Name: submissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.submissions_id_seq OWNED BY public.submissions.id;


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.system_settings (
    key character varying(100) NOT NULL,
    value text,
    type character varying(255) DEFAULT 'string'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT system_settings_type_check CHECK (((type)::text = ANY ((ARRAY['string'::character varying, 'number'::character varying, 'boolean'::character varying, 'json'::character varying])::text[])))
);


ALTER TABLE public.system_settings OWNER TO darrielmarkerizal;

--
-- Name: taggables; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.taggables (
    id bigint NOT NULL,
    tag_id bigint NOT NULL,
    taggable_type character varying(255) NOT NULL,
    taggable_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.taggables OWNER TO darrielmarkerizal;

--
-- Name: taggables_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.taggables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.taggables_id_seq OWNER TO darrielmarkerizal;

--
-- Name: taggables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.taggables_id_seq OWNED BY public.taggables.id;


--
-- Name: tags; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(120) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tags OWNER TO darrielmarkerizal;

--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tags_id_seq OWNER TO darrielmarkerizal;

--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: telescope_entries; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.telescope_entries (
    sequence bigint NOT NULL,
    uuid uuid NOT NULL,
    batch_id uuid NOT NULL,
    family_hash character varying(255),
    should_display_on_index boolean DEFAULT true NOT NULL,
    type character varying(20) NOT NULL,
    content text NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.telescope_entries OWNER TO darrielmarkerizal;

--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.telescope_entries_sequence_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.telescope_entries_sequence_seq OWNER TO darrielmarkerizal;

--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.telescope_entries_sequence_seq OWNED BY public.telescope_entries.sequence;


--
-- Name: telescope_entries_tags; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.telescope_entries_tags (
    entry_uuid uuid NOT NULL,
    tag character varying(255) NOT NULL
);


ALTER TABLE public.telescope_entries_tags OWNER TO darrielmarkerizal;

--
-- Name: telescope_monitoring; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.telescope_monitoring (
    tag character varying(255) NOT NULL
);


ALTER TABLE public.telescope_monitoring OWNER TO darrielmarkerizal;

--
-- Name: threads; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.threads (
    id bigint NOT NULL,
    scheme_id bigint NOT NULL,
    author_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    is_pinned boolean DEFAULT false NOT NULL,
    is_closed boolean DEFAULT false NOT NULL,
    is_resolved boolean DEFAULT false NOT NULL,
    views_count integer DEFAULT 0 NOT NULL,
    replies_count integer DEFAULT 0 NOT NULL,
    last_activity_at timestamp(0) without time zone,
    edited_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    deleted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.threads OWNER TO darrielmarkerizal;

--
-- Name: threads_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.threads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.threads_id_seq OWNER TO darrielmarkerizal;

--
-- Name: threads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.threads_id_seq OWNED BY public.threads.id;


--
-- Name: unit_progress; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.unit_progress (
    id bigint NOT NULL,
    enrollment_id bigint NOT NULL,
    unit_id bigint NOT NULL,
    status character varying(255) DEFAULT 'not_started'::character varying NOT NULL,
    progress_percent double precision DEFAULT '0'::double precision NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT unit_progress_status_check CHECK (((status)::text = ANY ((ARRAY['not_started'::character varying, 'in_progress'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.unit_progress OWNER TO darrielmarkerizal;

--
-- Name: unit_progress_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.unit_progress_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.unit_progress_id_seq OWNER TO darrielmarkerizal;

--
-- Name: unit_progress_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.unit_progress_id_seq OWNED BY public.unit_progress.id;


--
-- Name: units; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.units (
    id bigint NOT NULL,
    course_id bigint NOT NULL,
    code character varying(50) NOT NULL,
    slug character varying(100) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    "order" integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    CONSTRAINT units_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'published'::character varying])::text[])))
);


ALTER TABLE public.units OWNER TO darrielmarkerizal;

--
-- Name: units_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.units_id_seq OWNER TO darrielmarkerizal;

--
-- Name: units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.units_id_seq OWNED BY public.units.id;


--
-- Name: user_activities; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.user_activities (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    activity_type character varying(50) NOT NULL,
    activity_data json,
    related_type character varying(255),
    related_id bigint,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.user_activities OWNER TO darrielmarkerizal;

--
-- Name: user_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.user_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_activities_id_seq OWNER TO darrielmarkerizal;

--
-- Name: user_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.user_activities_id_seq OWNED BY public.user_activities.id;


--
-- Name: user_badges; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.user_badges (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    badge_id bigint NOT NULL,
    earned_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_badges OWNER TO darrielmarkerizal;

--
-- Name: user_badges_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.user_badges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_badges_id_seq OWNER TO darrielmarkerizal;

--
-- Name: user_badges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.user_badges_id_seq OWNED BY public.user_badges.id;


--
-- Name: user_challenge_assignments; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.user_challenge_assignments (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    challenge_id bigint NOT NULL,
    assigned_date date NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    completed_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    current_progress integer DEFAULT 0 NOT NULL,
    reward_claimed boolean DEFAULT false NOT NULL,
    CONSTRAINT user_challenge_assignments_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'expired'::character varying])::text[])))
);


ALTER TABLE public.user_challenge_assignments OWNER TO darrielmarkerizal;

--
-- Name: user_challenge_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.user_challenge_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_challenge_assignments_id_seq OWNER TO darrielmarkerizal;

--
-- Name: user_challenge_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.user_challenge_assignments_id_seq OWNED BY public.user_challenge_assignments.id;


--
-- Name: user_challenge_completions; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.user_challenge_completions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    challenge_id bigint NOT NULL,
    completed_date date NOT NULL,
    xp_earned integer DEFAULT 0 NOT NULL,
    completion_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_challenge_completions OWNER TO darrielmarkerizal;

--
-- Name: user_challenge_completions_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.user_challenge_completions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_challenge_completions_id_seq OWNER TO darrielmarkerizal;

--
-- Name: user_challenge_completions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.user_challenge_completions_id_seq OWNED BY public.user_challenge_completions.id;


--
-- Name: user_gamification_stats; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.user_gamification_stats (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    total_xp bigint DEFAULT '0'::bigint NOT NULL,
    total_points bigint DEFAULT '0'::bigint NOT NULL,
    global_level integer DEFAULT 1 NOT NULL,
    current_streak integer DEFAULT 0 NOT NULL,
    longest_streak integer DEFAULT 0 NOT NULL,
    total_badges integer DEFAULT 0 NOT NULL,
    completed_challenges integer DEFAULT 0 NOT NULL,
    last_activity_date date,
    stats_updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_gamification_stats OWNER TO darrielmarkerizal;

--
-- Name: user_gamification_stats_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.user_gamification_stats_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_gamification_stats_id_seq OWNER TO darrielmarkerizal;

--
-- Name: user_gamification_stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.user_gamification_stats_id_seq OWNED BY public.user_gamification_stats.id;


--
-- Name: user_notifications; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.user_notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    notification_id bigint NOT NULL,
    status character varying(255) DEFAULT 'unread'::character varying NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT user_notifications_status_check CHECK (((status)::text = ANY ((ARRAY['unread'::character varying, 'read'::character varying])::text[])))
);


ALTER TABLE public.user_notifications OWNER TO darrielmarkerizal;

--
-- Name: user_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.user_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_notifications_id_seq OWNER TO darrielmarkerizal;

--
-- Name: user_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.user_notifications_id_seq OWNED BY public.user_notifications.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: darrielmarkerizal
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    username character varying(50),
    email character varying(191) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    bio text,
    phone character varying(20),
    account_status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    last_profile_update timestamp(0) without time zone,
    is_password_set boolean DEFAULT true NOT NULL,
    CONSTRAINT users_account_status_check CHECK (((account_status)::text = ANY ((ARRAY['active'::character varying, 'suspended'::character varying, 'deleted'::character varying])::text[]))),
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'active'::character varying, 'inactive'::character varying, 'banned'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO darrielmarkerizal;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: darrielmarkerizal
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO darrielmarkerizal;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: darrielmarkerizal
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: announcements id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.announcements ALTER COLUMN id SET DEFAULT nextval('public.announcements_id_seq'::regclass);


--
-- Name: answers id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.answers ALTER COLUMN id SET DEFAULT nextval('public.answers_id_seq'::regclass);


--
-- Name: appeals id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.appeals ALTER COLUMN id SET DEFAULT nextval('public.appeals_id_seq'::regclass);


--
-- Name: assignment_prerequisites id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_prerequisites ALTER COLUMN id SET DEFAULT nextval('public.assignment_prerequisites_id_seq'::regclass);


--
-- Name: assignment_questions id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_questions ALTER COLUMN id SET DEFAULT nextval('public.assignment_questions_id_seq'::regclass);


--
-- Name: assignments id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignments ALTER COLUMN id SET DEFAULT nextval('public.assignments_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: audits id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.audits ALTER COLUMN id SET DEFAULT nextval('public.audits_id_seq'::regclass);


--
-- Name: badges id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.badges ALTER COLUMN id SET DEFAULT nextval('public.badges_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: certificates id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.certificates ALTER COLUMN id SET DEFAULT nextval('public.certificates_id_seq'::regclass);


--
-- Name: challenges id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.challenges ALTER COLUMN id SET DEFAULT nextval('public.challenges_id_seq'::regclass);


--
-- Name: content_categories id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_categories ALTER COLUMN id SET DEFAULT nextval('public.content_categories_id_seq'::regclass);


--
-- Name: content_reads id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_reads ALTER COLUMN id SET DEFAULT nextval('public.content_reads_id_seq'::regclass);


--
-- Name: content_revisions id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_revisions ALTER COLUMN id SET DEFAULT nextval('public.content_revisions_id_seq'::regclass);


--
-- Name: content_workflow_history id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_workflow_history ALTER COLUMN id SET DEFAULT nextval('public.content_workflow_history_id_seq'::regclass);


--
-- Name: course_admins id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_admins ALTER COLUMN id SET DEFAULT nextval('public.course_admins_id_seq'::regclass);


--
-- Name: course_outcomes id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_outcomes ALTER COLUMN id SET DEFAULT nextval('public.course_outcomes_id_seq'::regclass);


--
-- Name: course_progress id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_progress ALTER COLUMN id SET DEFAULT nextval('public.course_progress_id_seq'::regclass);


--
-- Name: course_tag_pivot id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_tag_pivot ALTER COLUMN id SET DEFAULT nextval('public.course_tag_pivot_id_seq'::regclass);


--
-- Name: courses id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses ALTER COLUMN id SET DEFAULT nextval('public.courses_id_seq'::regclass);


--
-- Name: enrollments id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.enrollments ALTER COLUMN id SET DEFAULT nextval('public.enrollments_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: forum_statistics id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.forum_statistics ALTER COLUMN id SET DEFAULT nextval('public.forum_statistics_id_seq'::regclass);


--
-- Name: gamification_milestones id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.gamification_milestones ALTER COLUMN id SET DEFAULT nextval('public.gamification_milestones_id_seq'::regclass);


--
-- Name: grade_reviews id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grade_reviews ALTER COLUMN id SET DEFAULT nextval('public.grade_reviews_id_seq'::regclass);


--
-- Name: grades id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grades ALTER COLUMN id SET DEFAULT nextval('public.grades_id_seq'::regclass);


--
-- Name: grading_rubrics id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grading_rubrics ALTER COLUMN id SET DEFAULT nextval('public.grading_rubrics_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: jwt_refresh_tokens id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jwt_refresh_tokens ALTER COLUMN id SET DEFAULT nextval('public.jwt_refresh_tokens_id_seq'::regclass);


--
-- Name: leaderboards id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.leaderboards ALTER COLUMN id SET DEFAULT nextval('public.leaderboards_id_seq'::regclass);


--
-- Name: learning_streaks id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.learning_streaks ALTER COLUMN id SET DEFAULT nextval('public.learning_streaks_id_seq'::regclass);


--
-- Name: lesson_blocks id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_blocks ALTER COLUMN id SET DEFAULT nextval('public.lesson_blocks_id_seq'::regclass);


--
-- Name: lesson_progress id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_progress ALTER COLUMN id SET DEFAULT nextval('public.lesson_progress_id_seq'::regclass);


--
-- Name: lessons id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lessons ALTER COLUMN id SET DEFAULT nextval('public.lessons_id_seq'::regclass);


--
-- Name: levels id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.levels ALTER COLUMN id SET DEFAULT nextval('public.levels_id_seq'::regclass);


--
-- Name: login_activities id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.login_activities ALTER COLUMN id SET DEFAULT nextval('public.login_activities_id_seq'::regclass);


--
-- Name: master_data id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.master_data ALTER COLUMN id SET DEFAULT nextval('public.master_data_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: news id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news ALTER COLUMN id SET DEFAULT nextval('public.news_id_seq'::regclass);


--
-- Name: notification_preferences id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_preferences ALTER COLUMN id SET DEFAULT nextval('public.notification_preferences_id_seq'::regclass);


--
-- Name: notification_templates id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_templates ALTER COLUMN id SET DEFAULT nextval('public.notification_templates_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: otp_codes id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.otp_codes ALTER COLUMN id SET DEFAULT nextval('public.otp_codes_id_seq'::regclass);


--
-- Name: overrides id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.overrides ALTER COLUMN id SET DEFAULT nextval('public.overrides_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: pinned_badges id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.pinned_badges ALTER COLUMN id SET DEFAULT nextval('public.pinned_badges_id_seq'::regclass);


--
-- Name: points id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.points ALTER COLUMN id SET DEFAULT nextval('public.points_id_seq'::regclass);


--
-- Name: profile_audit_logs id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_audit_logs ALTER COLUMN id SET DEFAULT nextval('public.profile_audit_logs_id_seq'::regclass);


--
-- Name: profile_privacy_settings id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_privacy_settings ALTER COLUMN id SET DEFAULT nextval('public.profile_privacy_settings_id_seq'::regclass);


--
-- Name: reactions id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reactions ALTER COLUMN id SET DEFAULT nextval('public.reactions_id_seq'::regclass);


--
-- Name: replies id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.replies ALTER COLUMN id SET DEFAULT nextval('public.replies_id_seq'::regclass);


--
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: search_history id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.search_history ALTER COLUMN id SET DEFAULT nextval('public.search_history_id_seq'::regclass);


--
-- Name: social_accounts id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.social_accounts ALTER COLUMN id SET DEFAULT nextval('public.social_accounts_id_seq'::regclass);


--
-- Name: submission_files id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submission_files ALTER COLUMN id SET DEFAULT nextval('public.submission_files_id_seq'::regclass);


--
-- Name: submissions id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submissions ALTER COLUMN id SET DEFAULT nextval('public.submissions_id_seq'::regclass);


--
-- Name: taggables id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.taggables ALTER COLUMN id SET DEFAULT nextval('public.taggables_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: telescope_entries sequence; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.telescope_entries ALTER COLUMN sequence SET DEFAULT nextval('public.telescope_entries_sequence_seq'::regclass);


--
-- Name: threads id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.threads ALTER COLUMN id SET DEFAULT nextval('public.threads_id_seq'::regclass);


--
-- Name: unit_progress id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.unit_progress ALTER COLUMN id SET DEFAULT nextval('public.unit_progress_id_seq'::regclass);


--
-- Name: units id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.units ALTER COLUMN id SET DEFAULT nextval('public.units_id_seq'::regclass);


--
-- Name: user_activities id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_activities ALTER COLUMN id SET DEFAULT nextval('public.user_activities_id_seq'::regclass);


--
-- Name: user_badges id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_badges ALTER COLUMN id SET DEFAULT nextval('public.user_badges_id_seq'::regclass);


--
-- Name: user_challenge_assignments id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_assignments ALTER COLUMN id SET DEFAULT nextval('public.user_challenge_assignments_id_seq'::regclass);


--
-- Name: user_challenge_completions id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_completions ALTER COLUMN id SET DEFAULT nextval('public.user_challenge_completions_id_seq'::regclass);


--
-- Name: user_gamification_stats id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_gamification_stats ALTER COLUMN id SET DEFAULT nextval('public.user_gamification_stats_id_seq'::regclass);


--
-- Name: user_notifications id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_notifications ALTER COLUMN id SET DEFAULT nextval('public.user_notifications_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at, updated_at, event, batch_uuid, ip_address, browser, browser_version, platform, device, device_type) FROM stdin;
\.


--
-- Data for Name: announcements; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.announcements (id, author_id, course_id, title, content, status, target_type, target_value, priority, published_at, scheduled_at, views_count, deleted_at, deleted_by, created_at, updated_at, slug) FROM stdin;
\.


--
-- Data for Name: answers; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.answers (id, submission_id, question_id, content, selected_options, file_paths, score, is_auto_graded, feedback, created_at, updated_at, files_expired_at, file_metadata) FROM stdin;
\.


--
-- Data for Name: appeals; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.appeals (id, submission_id, student_id, reviewer_id, reason, supporting_documents, status, decision_reason, submitted_at, decided_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: assignment_prerequisites; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.assignment_prerequisites (id, assignment_id, prerequisite_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: assignment_questions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.assignment_questions (id, assignment_id, type, content, options, answer_key, weight, "order", max_score, max_file_size, allowed_file_types, allow_multiple_files, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: assignments; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.assignments (id, lesson_id, created_by, title, description, submission_type, max_score, available_from, deadline_at, status, created_at, updated_at, allow_resubmit, late_penalty_percent, assignable_type, assignable_id, tolerance_minutes, max_attempts, cooldown_minutes, retake_enabled, review_mode, randomization_type, question_bank_count, time_limit_minutes) FROM stdin;
\.


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.audit_logs (id, event, target_type, target_id, actor_type, actor_id, user_id, properties, logged_at, created_at, updated_at, action, subject_type, subject_id, context) FROM stdin;
\.


--
-- Data for Name: audits; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.audits (id, action, actor_type, actor_id, user_id, target_table, target_type, target_id, module, context, ip_address, user_agent, meta, properties, logged_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: badges; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.badges (id, code, name, description, type, threshold, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.categories (id, name, value, description, status, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: certificates; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.certificates (id, user_id, course_id, certificate_number, issued_at, expired_at, status, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: challenges; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.challenges (id, title, description, type, points_reward, badge_id, start_at, end_at, created_at, updated_at, criteria, target_count) FROM stdin;
\.


--
-- Data for Name: content_categories; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.content_categories (id, name, slug, description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: content_reads; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.content_reads (id, user_id, readable_type, readable_id, read_at) FROM stdin;
\.


--
-- Data for Name: content_revisions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.content_revisions (id, content_type, content_id, editor_id, title, content, revision_note, created_at) FROM stdin;
\.


--
-- Data for Name: content_workflow_history; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.content_workflow_history (id, content_type, content_id, from_state, to_state, user_id, note, created_at) FROM stdin;
\.


--
-- Data for Name: course_admins; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.course_admins (id, course_id, user_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: course_outcomes; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.course_outcomes (id, course_id, outcome_text, "order", created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: course_progress; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.course_progress (id, enrollment_id, status, progress_percent, started_at, completed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: course_tag_pivot; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.course_tag_pivot (id, course_id, tag_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: courses; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.courses (id, code, slug, title, short_desc, type, level_tag, tags_json, enrollment_type, progression_mode, status, published_at, created_at, updated_at, deleted_at, instructor_id, category_id, prereq_json, prereq_text, deleted_by, enrollment_key_hash) FROM stdin;
\.


--
-- Data for Name: enrollments; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.enrollments (id, user_id, course_id, status, enrolled_at, completed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: forum_statistics; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.forum_statistics (id, scheme_id, user_id, threads_count, replies_count, views_count, avg_response_time_minutes, response_rate, period_start, period_end, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: gamification_milestones; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.gamification_milestones (id, code, name, description, xp_required, level_required, sort_order, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: grade_reviews; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.grade_reviews (id, grade_id, requested_by, reason, response, reviewed_by, status, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: grades; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.grades (id, source_type, source_id, user_id, graded_by, score, max_score, feedback, status, graded_at, created_at, updated_at, submission_id, original_score, is_override, override_reason, is_draft, released_at) FROM stdin;
\.


--
-- Data for Name: grading_rubrics; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.grading_rubrics (id, scope_type, scope_id, criteria, description, max_score, weight, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: jwt_refresh_tokens; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.jwt_refresh_tokens (id, user_id, token, ip, user_agent, revoked_at, expires_at, created_at, updated_at, device_id, replaced_by, last_used_at, idle_expires_at, absolute_expires_at) FROM stdin;
\.


--
-- Data for Name: leaderboards; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.leaderboards (id, course_id, user_id, rank, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: learning_streaks; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.learning_streaks (id, user_id, activity_date, xp_earned, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: lesson_blocks; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.lesson_blocks (id, lesson_id, block_type, content, "order", created_at, updated_at, slug) FROM stdin;
\.


--
-- Data for Name: lesson_progress; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.lesson_progress (id, enrollment_id, lesson_id, status, progress_percent, attempt_count, started_at, completed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: lessons; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.lessons (id, unit_id, slug, title, description, markdown_content, content_type, content_url, "order", duration_minutes, created_at, updated_at, status, published_at) FROM stdin;
\.


--
-- Data for Name: levels; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.levels (id, user_id, course_id, current_level, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: login_activities; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.login_activities (id, user_id, ip, user_agent, status, logged_in_at, logged_out_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: master_data; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.master_data (id, type, value, label, metadata, is_system, is_active, sort_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: media; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.media (id, model_type, model_id, uuid, collection_name, name, file_name, mime_type, disk, conversions_disk, size, manipulations, custom_properties, generated_conversions, responsive_images, order_column, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2024_12_07_140000_create_master_data_table	1
2	2025_11_01_143748_create_permission_tables	1
3	2025_11_02_101115_create_users_table	1
4	2025_11_02_102132_create_social_accounts_table	1
5	2025_11_02_102315_create_jwt_refresh_tokens_table	1
6	2025_11_02_102331_create_login_activities_table	1
7	2025_11_02_102343_create_otp_codes_table	1
8	2025_11_02_102427_create_password_reset_tokens_table	1
9	2025_11_02_102815_create_cache_table	1
10	2025_11_02_103710_create_system_settings_table	1
11	2025_11_02_103715_create_audit_logs_table	1
12	2025_11_02_115520_create_courses_table	1
13	2025_11_02_115526_create_course_tags_table	1
14	2025_11_02_115531_create_units_table	1
15	2025_11_02_115536_create_lessons_table	1
16	2025_11_02_115542_create_lesson_blocks_table	1
17	2025_11_02_120736_create_course_admins_table	1
18	2025_11_02_120741_add_instructor_id_to_courses_table	1
19	2025_11_02_121910_create_enrollments_table	1
20	2025_11_02_122003_create_unit_progress_table	1
21	2025_11_02_122055_create_lesson_progress_table	1
22	2025_11_02_122455_create_assignments_table	1
23	2025_11_02_122609_create_submissions_table	1
24	2025_11_02_122631_create_submission_files_table	1
25	2025_11_02_125033_create_grading_rubrics_table	1
26	2025_11_02_125104_create_grades_table	1
27	2025_11_02_125422_create_grade_reviews_table	1
28	2025_11_02_130354_create_points_table	1
29	2025_11_02_130429_create_badges_table	1
30	2025_11_02_130454_create_user_badges_table	1
31	2025_11_02_130523_create_levels_table	1
32	2025_11_02_130545_create_challenges_table	1
33	2025_11_02_130608_create_leaderboards_table	1
34	2025_11_03_062435_create_notifications_table	1
35	2025_11_03_062446_create_user_notifications_table	1
36	2025_11_03_062532_create_notification_templates_table	1
37	2025_11_03_062941_create_certificates_table	1
38	2025_11_03_063018_create_reports_table	1
39	2025_11_03_063045_create_system_audits_table	1
40	2025_11_03_071605_create_sessions_table	1
41	2025_11_03_090300_create_jobs_table	1
42	2025_11_03_090301_create_failed_jobs_table	1
43	2025_11_03_090400_add_uuid_to_otp_codes_table	1
44	2025_11_03_140000_add_meta_to_otp_codes_table	1
45	2025_11_04_000000_create_categories_table	1
46	2025_11_04_000001_drop_category_column_from_courses	1
47	2025_11_04_000002_update_courses_prereq_to_json	1
48	2025_11_04_000003_drop_course_tags_table	1
49	2025_11_04_000004_update_units_add_status_and_unique_slug_per_course	1
50	2025_11_04_000005_update_lessons_add_status_and_unique_slug_per_unit	1
51	2025_11_05_000010_alter_lesson_blocks_add_video_and_meta	1
52	2025_11_06_000011_add_slug_to_lesson_blocks	1
53	2025_11_07_040326_update_social_accounts_token_columns_to_text	1
54	2025_11_10_064543_create_learning_streaks_table	1
55	2025_11_10_064549_create_user_challenge_completions_table	1
56	2025_11_10_065824_create_user_gamification_stats_table	1
57	2025_11_10_065844_create_user_challenge_assignments_table	1
58	2025_11_11_120000_update_jwt_refresh_tokens_table_for_rotating_tokens	1
59	2025_11_12_054318_make_username_nullable_in_users_table	1
60	2025_11_12_143453_add_resubmit_fields_to_assignments_table	1
61	2025_11_13_000000_create_course_progress_table	1
62	2025_11_13_010000_create_tags_table	1
63	2025_11_13_010100_create_course_tag_pivot_table	1
64	2025_11_13_012435_create_telescope_entries_table	1
65	2025_11_13_040000_update_courses_enrollment_fields	1
66	2025_11_13_052646_consolidate_audit_tables_into_audits_table	1
67	2025_11_13_052651_remove_progress_percent_from_enrollments	1
68	2025_11_13_052656_remove_score_feedback_from_submissions	1
69	2025_11_13_052659_remove_course_id_from_course_progress	1
70	2025_11_13_052704_normalize_course_outcomes_and_prerequisites	1
71	2025_11_13_070010_reintroduce_prereq_text_and_drop_course_prerequisites	1
72	2025_11_13_100000_update_submissions_add_resubmission_fields	1
73	2025_12_02_091553_add_profile_fields_to_users_table	1
74	2025_12_02_091602_create_profile_privacy_settings_table	1
75	2025_12_02_091612_create_user_activities_table	1
76	2025_12_02_091622_create_pinned_badges_table	1
77	2025_12_02_091622_create_profile_audit_logs_table	1
78	2025_12_02_092834_add_privacy_settings_to_existing_users	1
79	2025_12_02_143456_create_threads_table	1
80	2025_12_02_143457_create_replies_table	1
81	2025_12_02_143458_create_reactions_table	1
82	2025_12_02_143459_create_forum_statistics_table	1
83	2025_12_02_150912_create_announcements_table	1
84	2025_12_02_150913_create_news_table	1
85	2025_12_02_150914_create_content_reads_table	1
86	2025_12_02_150915_create_content_revisions_table	1
87	2025_12_02_150916_create_content_categories_table	1
88	2025_12_02_150920_create_taggables_table	1
89	2025_12_03_000001_create_search_history_table	1
90	2025_12_03_050122_create_content_workflow_history_table	1
91	2025_12_03_052158_add_workflow_statuses_to_content_tables	1
92	2025_12_03_100000_create_notification_preferences_table	1
93	2025_12_03_200001_add_deleted_by_to_courses_table	1
94	2025_12_03_200001_convert_notification_preferences_strings_to_enum	1
95	2025_12_03_210000_add_enrollment_key_hash_to_courses_table	1
96	2025_12_04_100001_add_criteria_to_challenges_table	1
97	2025_12_04_100002_add_progress_to_user_challenge_assignments_table	1
98	2025_12_04_100003_add_challenge_source_type_to_points_table	1
99	2025_12_07_080512_create_activity_log_table	1
100	2025_12_07_080513_add_event_column_to_activity_log_table	1
101	2025_12_07_080514_add_batch_uuid_column_to_activity_log_table	1
102	2025_12_07_133606_create_media_table	1
103	2025_12_07_135219_drop_old_file_path_columns	1
104	2025_12_17_025732_add_browser_fields_to_activity_log_table	1
105	2025_12_19_020755_create_gamification_milestones_table	1
106	2026_01_07_113944_add_account_deletion_to_otp_codes_purpose	1
107	2026_01_08_053244_add_is_password_set_to_users_table	1
108	2026_01_11_214230_add_slug_to_announcements_table	1
109	2026_01_11_214648_update_notification_preferences_category_constraint	1
110	2026_01_11_223000_update_notifications_type_constraint	1
111	2026_01_18_022749_add_performance_indexes_for_courses_and_units	1
112	2026_01_18_133615_add_performance_indexes_to_auth_tables	1
113	2026_01_18_143328_add_filtering_indexes_to_schemes_tables	1
114	2026_01_21_000001_add_assessment_grading_fields_to_assignments	1
115	2026_01_21_000001_add_assessment_grading_fields_to_grades	1
116	2026_01_21_000002_add_assessment_grading_fields_to_submissions	1
117	2026_01_21_000002_create_appeals_table	1
118	2026_01_21_000003_create_questions_table	1
119	2026_01_21_000004_create_answers_table	1
120	2026_01_21_000005_create_assignment_prerequisites_table	1
121	2026_01_21_000006_add_state_field_to_submissions	1
122	2026_01_21_000007_create_overrides_table	1
123	2026_01_21_000008_add_assessment_grading_fields_to_audit_logs	1
124	2026_01_21_000008_add_file_retention_fields_to_answers	1
125	2026_01_21_000009_add_performance_indexes	1
126	2026_01_23_181120_make_lesson_id_nullable_in_assignments	1
127	2026_01_24_000001_add_time_limit_to_assignments_and_submissions	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.news (id, author_id, title, slug, excerpt, content, status, is_featured, published_at, scheduled_at, views_count, deleted_at, deleted_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: news_category; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.news_category (news_id, category_id) FROM stdin;
\.


--
-- Data for Name: notification_preferences; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.notification_preferences (id, user_id, category, channel, enabled, frequency, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: notification_templates; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.notification_templates (id, code, title, body, channel, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.notifications (id, type, title, message, data, action_url, channel, priority, is_broadcast, scheduled_at, sent_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: otp_codes; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.otp_codes (id, user_id, channel, provider, purpose, code, expires_at, consumed_at, created_at, updated_at, uuid, meta) FROM stdin;
\.


--
-- Data for Name: overrides; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.overrides (id, assignment_id, student_id, grantor_id, type, reason, value, granted_at, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: pinned_badges; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.pinned_badges (id, user_id, badge_id, "order", created_at) FROM stdin;
\.


--
-- Data for Name: points; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.points (id, user_id, source_type, source_id, points, reason, description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: profile_audit_logs; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.profile_audit_logs (id, user_id, admin_id, action, changes, ip_address, user_agent, created_at) FROM stdin;
\.


--
-- Data for Name: profile_privacy_settings; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.profile_privacy_settings (id, user_id, profile_visibility, show_email, show_phone, show_activity_history, show_achievements, show_statistics, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reactions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.reactions (id, user_id, reactable_type, reactable_id, type, created_at) FROM stdin;
\.


--
-- Data for Name: replies; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.replies (id, thread_id, parent_id, author_id, content, depth, is_accepted_answer, edited_at, deleted_at, deleted_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reports; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.reports (id, type, generated_by, filters, notes, generated_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.roles (id, name, guard_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: search_history; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.search_history (id, user_id, query, filters, results_count, clicked_result_id, clicked_result_type, created_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: social_accounts; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.social_accounts (id, user_id, provider_name, provider_id, token, refresh_token, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: submission_files; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.submission_files (id, submission_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: submissions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.submissions (id, assignment_id, user_id, enrollment_id, answer_text, status, submitted_at, created_at, updated_at, attempt_number, is_late, is_resubmission, previous_submission_id, score, question_set, state, started_at, time_expired_at, auto_submitted_on_timeout) FROM stdin;
\.


--
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.system_settings (key, value, type, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: taggables; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.taggables (id, tag_id, taggable_type, taggable_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.tags (id, name, slug, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: telescope_entries; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.telescope_entries (sequence, uuid, batch_id, family_hash, should_display_on_index, type, content, created_at) FROM stdin;
\.


--
-- Data for Name: telescope_entries_tags; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.telescope_entries_tags (entry_uuid, tag) FROM stdin;
\.


--
-- Data for Name: telescope_monitoring; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.telescope_monitoring (tag) FROM stdin;
\.


--
-- Data for Name: threads; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.threads (id, scheme_id, author_id, title, content, is_pinned, is_closed, is_resolved, views_count, replies_count, last_activity_at, edited_at, deleted_at, deleted_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: unit_progress; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.unit_progress (id, enrollment_id, unit_id, status, progress_percent, started_at, completed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: units; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.units (id, course_id, code, slug, title, description, "order", created_at, updated_at, status) FROM stdin;
\.


--
-- Data for Name: user_activities; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.user_activities (id, user_id, activity_type, activity_data, related_type, related_id, created_at) FROM stdin;
\.


--
-- Data for Name: user_badges; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.user_badges (id, user_id, badge_id, earned_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_challenge_assignments; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.user_challenge_assignments (id, user_id, challenge_id, assigned_date, status, completed_at, expires_at, created_at, updated_at, current_progress, reward_claimed) FROM stdin;
\.


--
-- Data for Name: user_challenge_completions; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.user_challenge_completions (id, user_id, challenge_id, completed_date, xp_earned, completion_data, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_gamification_stats; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.user_gamification_stats (id, user_id, total_xp, total_points, global_level, current_streak, longest_streak, total_badges, completed_challenges, last_activity_date, stats_updated_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_notifications; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.user_notifications (id, user_id, notification_id, status, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: darrielmarkerizal
--

COPY public.users (id, name, username, email, email_verified_at, password, status, remember_token, created_at, updated_at, deleted_at, bio, phone, account_status, last_profile_update, is_password_set) FROM stdin;
\.


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 1, false);


--
-- Name: announcements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.announcements_id_seq', 1, false);


--
-- Name: answers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.answers_id_seq', 1, false);


--
-- Name: appeals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.appeals_id_seq', 1, false);


--
-- Name: assignment_prerequisites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.assignment_prerequisites_id_seq', 1, false);


--
-- Name: assignment_questions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.assignment_questions_id_seq', 1, false);


--
-- Name: assignments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.assignments_id_seq', 1, false);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 1, false);


--
-- Name: audits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.audits_id_seq', 1, false);


--
-- Name: badges_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.badges_id_seq', 1, false);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.categories_id_seq', 1, false);


--
-- Name: certificates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.certificates_id_seq', 1, false);


--
-- Name: challenges_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.challenges_id_seq', 1, false);


--
-- Name: content_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.content_categories_id_seq', 1, false);


--
-- Name: content_reads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.content_reads_id_seq', 1, false);


--
-- Name: content_revisions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.content_revisions_id_seq', 1, false);


--
-- Name: content_workflow_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.content_workflow_history_id_seq', 1, false);


--
-- Name: course_admins_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.course_admins_id_seq', 1, false);


--
-- Name: course_outcomes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.course_outcomes_id_seq', 1, false);


--
-- Name: course_progress_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.course_progress_id_seq', 1, false);


--
-- Name: course_tag_pivot_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.course_tag_pivot_id_seq', 1, false);


--
-- Name: courses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.courses_id_seq', 1, false);


--
-- Name: enrollments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.enrollments_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: forum_statistics_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.forum_statistics_id_seq', 1, false);


--
-- Name: gamification_milestones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.gamification_milestones_id_seq', 1, false);


--
-- Name: grade_reviews_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.grade_reviews_id_seq', 1, false);


--
-- Name: grades_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.grades_id_seq', 1, false);


--
-- Name: grading_rubrics_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.grading_rubrics_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: jwt_refresh_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.jwt_refresh_tokens_id_seq', 1, false);


--
-- Name: leaderboards_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.leaderboards_id_seq', 1, false);


--
-- Name: learning_streaks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.learning_streaks_id_seq', 1, false);


--
-- Name: lesson_blocks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.lesson_blocks_id_seq', 1, false);


--
-- Name: lesson_progress_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.lesson_progress_id_seq', 1, false);


--
-- Name: lessons_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.lessons_id_seq', 1, false);


--
-- Name: levels_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.levels_id_seq', 1, false);


--
-- Name: login_activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.login_activities_id_seq', 1, false);


--
-- Name: master_data_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.master_data_id_seq', 1, false);


--
-- Name: media_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.media_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.migrations_id_seq', 127, true);


--
-- Name: news_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.news_id_seq', 1, false);


--
-- Name: notification_preferences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.notification_preferences_id_seq', 1, false);


--
-- Name: notification_templates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.notification_templates_id_seq', 1, false);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.notifications_id_seq', 1, false);


--
-- Name: otp_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.otp_codes_id_seq', 1, false);


--
-- Name: overrides_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.overrides_id_seq', 1, false);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.permissions_id_seq', 1, false);


--
-- Name: pinned_badges_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.pinned_badges_id_seq', 1, false);


--
-- Name: points_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.points_id_seq', 1, false);


--
-- Name: profile_audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.profile_audit_logs_id_seq', 1, false);


--
-- Name: profile_privacy_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.profile_privacy_settings_id_seq', 1, false);


--
-- Name: reactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.reactions_id_seq', 1, false);


--
-- Name: replies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.replies_id_seq', 1, false);


--
-- Name: reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.reports_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.roles_id_seq', 1, false);


--
-- Name: search_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.search_history_id_seq', 1, false);


--
-- Name: social_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.social_accounts_id_seq', 1, false);


--
-- Name: submission_files_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.submission_files_id_seq', 1, false);


--
-- Name: submissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.submissions_id_seq', 1, false);


--
-- Name: taggables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.taggables_id_seq', 1, false);


--
-- Name: tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.tags_id_seq', 1, false);


--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.telescope_entries_sequence_seq', 1, false);


--
-- Name: threads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.threads_id_seq', 1, false);


--
-- Name: unit_progress_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.unit_progress_id_seq', 1, false);


--
-- Name: units_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.units_id_seq', 1, false);


--
-- Name: user_activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.user_activities_id_seq', 1, false);


--
-- Name: user_badges_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.user_badges_id_seq', 1, false);


--
-- Name: user_challenge_assignments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.user_challenge_assignments_id_seq', 1, false);


--
-- Name: user_challenge_completions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.user_challenge_completions_id_seq', 1, false);


--
-- Name: user_gamification_stats_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.user_gamification_stats_id_seq', 1, false);


--
-- Name: user_notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.user_notifications_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: darrielmarkerizal
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: announcements announcements_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_pkey PRIMARY KEY (id);


--
-- Name: announcements announcements_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_slug_unique UNIQUE (slug);


--
-- Name: answers answers_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.answers
    ADD CONSTRAINT answers_pkey PRIMARY KEY (id);


--
-- Name: appeals appeals_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.appeals
    ADD CONSTRAINT appeals_pkey PRIMARY KEY (id);


--
-- Name: assignment_prerequisites assignment_prerequisites_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_prerequisites
    ADD CONSTRAINT assignment_prerequisites_pkey PRIMARY KEY (id);


--
-- Name: assignment_questions assignment_questions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_questions
    ADD CONSTRAINT assignment_questions_pkey PRIMARY KEY (id);


--
-- Name: assignments assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: audits audits_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_pkey PRIMARY KEY (id);


--
-- Name: badges badges_code_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.badges
    ADD CONSTRAINT badges_code_unique UNIQUE (code);


--
-- Name: badges badges_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.badges
    ADD CONSTRAINT badges_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_value_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_value_unique UNIQUE (value);


--
-- Name: certificates certificates_certificate_number_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_certificate_number_unique UNIQUE (certificate_number);


--
-- Name: certificates certificates_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_pkey PRIMARY KEY (id);


--
-- Name: challenges challenges_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.challenges
    ADD CONSTRAINT challenges_pkey PRIMARY KEY (id);


--
-- Name: content_categories content_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_categories
    ADD CONSTRAINT content_categories_pkey PRIMARY KEY (id);


--
-- Name: content_categories content_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_categories
    ADD CONSTRAINT content_categories_slug_unique UNIQUE (slug);


--
-- Name: content_reads content_reads_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_reads
    ADD CONSTRAINT content_reads_pkey PRIMARY KEY (id);


--
-- Name: content_revisions content_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_revisions
    ADD CONSTRAINT content_revisions_pkey PRIMARY KEY (id);


--
-- Name: content_workflow_history content_workflow_history_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_workflow_history
    ADD CONSTRAINT content_workflow_history_pkey PRIMARY KEY (id);


--
-- Name: course_admins course_admins_course_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_admins
    ADD CONSTRAINT course_admins_course_id_user_id_unique UNIQUE (course_id, user_id);


--
-- Name: course_admins course_admins_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_admins
    ADD CONSTRAINT course_admins_pkey PRIMARY KEY (id);


--
-- Name: course_outcomes course_outcomes_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_outcomes
    ADD CONSTRAINT course_outcomes_pkey PRIMARY KEY (id);


--
-- Name: course_progress course_progress_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_progress
    ADD CONSTRAINT course_progress_pkey PRIMARY KEY (id);


--
-- Name: course_tag_pivot course_tag_pivot_course_id_tag_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_tag_pivot
    ADD CONSTRAINT course_tag_pivot_course_id_tag_id_unique UNIQUE (course_id, tag_id);


--
-- Name: course_tag_pivot course_tag_pivot_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_tag_pivot
    ADD CONSTRAINT course_tag_pivot_pkey PRIMARY KEY (id);


--
-- Name: courses courses_code_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_code_unique UNIQUE (code);


--
-- Name: courses courses_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_pkey PRIMARY KEY (id);


--
-- Name: courses courses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_slug_unique UNIQUE (slug);


--
-- Name: enrollments enrollments_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.enrollments
    ADD CONSTRAINT enrollments_pkey PRIMARY KEY (id);


--
-- Name: enrollments enrollments_user_id_course_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.enrollments
    ADD CONSTRAINT enrollments_user_id_course_id_unique UNIQUE (user_id, course_id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: forum_statistics forum_statistics_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.forum_statistics
    ADD CONSTRAINT forum_statistics_pkey PRIMARY KEY (id);


--
-- Name: gamification_milestones gamification_milestones_code_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.gamification_milestones
    ADD CONSTRAINT gamification_milestones_code_unique UNIQUE (code);


--
-- Name: gamification_milestones gamification_milestones_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.gamification_milestones
    ADD CONSTRAINT gamification_milestones_pkey PRIMARY KEY (id);


--
-- Name: grade_reviews grade_reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grade_reviews
    ADD CONSTRAINT grade_reviews_pkey PRIMARY KEY (id);


--
-- Name: grades grades_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grades
    ADD CONSTRAINT grades_pkey PRIMARY KEY (id);


--
-- Name: grading_rubrics grading_rubrics_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grading_rubrics
    ADD CONSTRAINT grading_rubrics_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: jwt_refresh_tokens jwt_refresh_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jwt_refresh_tokens
    ADD CONSTRAINT jwt_refresh_tokens_pkey PRIMARY KEY (id);


--
-- Name: jwt_refresh_tokens jwt_refresh_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jwt_refresh_tokens
    ADD CONSTRAINT jwt_refresh_tokens_token_unique UNIQUE (token);


--
-- Name: leaderboards leaderboards_course_user_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.leaderboards
    ADD CONSTRAINT leaderboards_course_user_unique UNIQUE (course_id, user_id);


--
-- Name: leaderboards leaderboards_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.leaderboards
    ADD CONSTRAINT leaderboards_pkey PRIMARY KEY (id);


--
-- Name: learning_streaks learning_streaks_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.learning_streaks
    ADD CONSTRAINT learning_streaks_pkey PRIMARY KEY (id);


--
-- Name: learning_streaks learning_streaks_user_id_activity_date_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.learning_streaks
    ADD CONSTRAINT learning_streaks_user_id_activity_date_unique UNIQUE (user_id, activity_date);


--
-- Name: lesson_blocks lesson_blocks_lesson_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_blocks
    ADD CONSTRAINT lesson_blocks_lesson_id_slug_unique UNIQUE (lesson_id, slug);


--
-- Name: lesson_blocks lesson_blocks_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_blocks
    ADD CONSTRAINT lesson_blocks_pkey PRIMARY KEY (id);


--
-- Name: lesson_progress lesson_progress_enrollment_id_lesson_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_progress
    ADD CONSTRAINT lesson_progress_enrollment_id_lesson_id_unique UNIQUE (enrollment_id, lesson_id);


--
-- Name: lesson_progress lesson_progress_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_progress
    ADD CONSTRAINT lesson_progress_pkey PRIMARY KEY (id);


--
-- Name: lessons lessons_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lessons
    ADD CONSTRAINT lessons_pkey PRIMARY KEY (id);


--
-- Name: lessons lessons_unit_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lessons
    ADD CONSTRAINT lessons_unit_id_slug_unique UNIQUE (unit_id, slug);


--
-- Name: levels levels_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_pkey PRIMARY KEY (id);


--
-- Name: levels levels_user_course_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_user_course_unique UNIQUE (user_id, course_id);


--
-- Name: login_activities login_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.login_activities
    ADD CONSTRAINT login_activities_pkey PRIMARY KEY (id);


--
-- Name: master_data master_data_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.master_data
    ADD CONSTRAINT master_data_pkey PRIMARY KEY (id);


--
-- Name: master_data master_data_type_value_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.master_data
    ADD CONSTRAINT master_data_type_value_unique UNIQUE (type, value);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: media media_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_uuid_unique UNIQUE (uuid);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: news_category news_category_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news_category
    ADD CONSTRAINT news_category_pkey PRIMARY KEY (news_id, category_id);


--
-- Name: news news_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_pkey PRIMARY KEY (id);


--
-- Name: news news_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_slug_unique UNIQUE (slug);


--
-- Name: notification_preferences notification_preferences_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_pkey PRIMARY KEY (id);


--
-- Name: notification_templates notification_templates_code_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_templates
    ADD CONSTRAINT notification_templates_code_unique UNIQUE (code);


--
-- Name: notification_templates notification_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_templates
    ADD CONSTRAINT notification_templates_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: otp_codes otp_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.otp_codes
    ADD CONSTRAINT otp_codes_pkey PRIMARY KEY (id);


--
-- Name: overrides overrides_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.overrides
    ADD CONSTRAINT overrides_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: pinned_badges pinned_badges_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.pinned_badges
    ADD CONSTRAINT pinned_badges_pkey PRIMARY KEY (id);


--
-- Name: points points_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.points
    ADD CONSTRAINT points_pkey PRIMARY KEY (id);


--
-- Name: profile_audit_logs profile_audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_audit_logs
    ADD CONSTRAINT profile_audit_logs_pkey PRIMARY KEY (id);


--
-- Name: profile_privacy_settings profile_privacy_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_privacy_settings
    ADD CONSTRAINT profile_privacy_settings_pkey PRIMARY KEY (id);


--
-- Name: profile_privacy_settings profile_privacy_settings_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_privacy_settings
    ADD CONSTRAINT profile_privacy_settings_user_id_unique UNIQUE (user_id);


--
-- Name: reactions reactions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reactions
    ADD CONSTRAINT reactions_pkey PRIMARY KEY (id);


--
-- Name: replies replies_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.replies
    ADD CONSTRAINT replies_pkey PRIMARY KEY (id);


--
-- Name: reports reports_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: search_history search_history_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.search_history
    ADD CONSTRAINT search_history_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: social_accounts social_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.social_accounts
    ADD CONSTRAINT social_accounts_pkey PRIMARY KEY (id);


--
-- Name: social_accounts social_accounts_provider_name_provider_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.social_accounts
    ADD CONSTRAINT social_accounts_provider_name_provider_id_unique UNIQUE (provider_name, provider_id);


--
-- Name: submission_files submission_files_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submission_files
    ADD CONSTRAINT submission_files_pkey PRIMARY KEY (id);


--
-- Name: submissions submissions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submissions
    ADD CONSTRAINT submissions_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (key);


--
-- Name: taggables taggables_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_pkey PRIMARY KEY (id);


--
-- Name: taggables taggables_tag_id_taggable_id_taggable_type_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_tag_id_taggable_id_taggable_type_unique UNIQUE (tag_id, taggable_id, taggable_type);


--
-- Name: tags tags_name_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_name_unique UNIQUE (name);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: tags tags_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_slug_unique UNIQUE (slug);


--
-- Name: telescope_entries telescope_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_pkey PRIMARY KEY (sequence);


--
-- Name: telescope_entries_tags telescope_entries_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_pkey PRIMARY KEY (entry_uuid, tag);


--
-- Name: telescope_entries telescope_entries_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_uuid_unique UNIQUE (uuid);


--
-- Name: telescope_monitoring telescope_monitoring_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.telescope_monitoring
    ADD CONSTRAINT telescope_monitoring_pkey PRIMARY KEY (tag);


--
-- Name: threads threads_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.threads
    ADD CONSTRAINT threads_pkey PRIMARY KEY (id);


--
-- Name: user_challenge_assignments uca_user_challenge_date_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_assignments
    ADD CONSTRAINT uca_user_challenge_date_unique UNIQUE (user_id, challenge_id, assigned_date);


--
-- Name: user_challenge_completions ucc_user_challenge_date_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_completions
    ADD CONSTRAINT ucc_user_challenge_date_unique UNIQUE (user_id, challenge_id, completed_date);


--
-- Name: answers uniq_answers_submission_question; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.answers
    ADD CONSTRAINT uniq_answers_submission_question UNIQUE (submission_id, question_id);


--
-- Name: assignment_prerequisites uniq_assignment_prerequisite; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_prerequisites
    ADD CONSTRAINT uniq_assignment_prerequisite UNIQUE (assignment_id, prerequisite_id);


--
-- Name: forum_statistics unique_stat; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.forum_statistics
    ADD CONSTRAINT unique_stat UNIQUE (scheme_id, user_id, period_start, period_end);


--
-- Name: pinned_badges unique_user_badge; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.pinned_badges
    ADD CONSTRAINT unique_user_badge UNIQUE (user_id, badge_id);


--
-- Name: notification_preferences unique_user_category_channel; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT unique_user_category_channel UNIQUE (user_id, category, channel);


--
-- Name: reactions unique_user_reaction; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reactions
    ADD CONSTRAINT unique_user_reaction UNIQUE (user_id, reactable_type, reactable_id, type);


--
-- Name: content_reads unique_user_read; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_reads
    ADD CONSTRAINT unique_user_read UNIQUE (user_id, readable_type, readable_id);


--
-- Name: unit_progress unit_progress_enrollment_id_unit_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.unit_progress
    ADD CONSTRAINT unit_progress_enrollment_id_unit_id_unique UNIQUE (enrollment_id, unit_id);


--
-- Name: unit_progress unit_progress_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.unit_progress
    ADD CONSTRAINT unit_progress_pkey PRIMARY KEY (id);


--
-- Name: units units_code_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_code_unique UNIQUE (code);


--
-- Name: units units_course_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_course_id_slug_unique UNIQUE (course_id, slug);


--
-- Name: units units_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_pkey PRIMARY KEY (id);


--
-- Name: user_activities user_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_activities
    ADD CONSTRAINT user_activities_pkey PRIMARY KEY (id);


--
-- Name: user_badges user_badges_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_badges
    ADD CONSTRAINT user_badges_pkey PRIMARY KEY (id);


--
-- Name: user_badges user_badges_user_id_badge_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_badges
    ADD CONSTRAINT user_badges_user_id_badge_id_unique UNIQUE (user_id, badge_id);


--
-- Name: user_challenge_assignments user_challenge_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_assignments
    ADD CONSTRAINT user_challenge_assignments_pkey PRIMARY KEY (id);


--
-- Name: user_challenge_completions user_challenge_completions_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_completions
    ADD CONSTRAINT user_challenge_completions_pkey PRIMARY KEY (id);


--
-- Name: user_gamification_stats user_gamification_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_gamification_stats
    ADD CONSTRAINT user_gamification_stats_pkey PRIMARY KEY (id);


--
-- Name: user_gamification_stats user_gamification_stats_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_gamification_stats
    ADD CONSTRAINT user_gamification_stats_user_id_unique UNIQUE (user_id);


--
-- Name: user_notifications user_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_notifications
    ADD CONSTRAINT user_notifications_pkey PRIMARY KEY (id);


--
-- Name: user_notifications user_notifications_user_id_notification_id_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_notifications
    ADD CONSTRAINT user_notifications_user_id_notification_id_unique UNIQUE (user_id, notification_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: activity_log_device_type_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX activity_log_device_type_index ON public.activity_log USING btree (device_type);


--
-- Name: activity_log_ip_address_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX activity_log_ip_address_index ON public.activity_log USING btree (ip_address);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: announcements_course_id_published_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX announcements_course_id_published_at_index ON public.announcements USING btree (course_id, published_at);


--
-- Name: announcements_slug_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX announcements_slug_index ON public.announcements USING btree (slug);


--
-- Name: announcements_status_published_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX announcements_status_published_at_index ON public.announcements USING btree (status, published_at);


--
-- Name: announcements_title_content_fulltext; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX announcements_title_content_fulltext ON public.announcements USING gin (((to_tsvector('english'::regconfig, (title)::text) || to_tsvector('english'::regconfig, content))));


--
-- Name: appeals_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX appeals_status_index ON public.appeals USING btree (status);


--
-- Name: appeals_student_id_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX appeals_student_id_status_index ON public.appeals USING btree (student_id, status);


--
-- Name: appeals_submission_id_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX appeals_submission_id_status_index ON public.appeals USING btree (submission_id, status);


--
-- Name: assignments_lesson_id_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX assignments_lesson_id_status_index ON public.assignments USING btree (lesson_id, status);


--
-- Name: audit_logs_event_logged_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audit_logs_event_logged_at_index ON public.audit_logs USING btree (event, logged_at);


--
-- Name: audit_logs_target_type_target_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audit_logs_target_type_target_id_index ON public.audit_logs USING btree (target_type, target_id);


--
-- Name: audits_action_logged_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audits_action_logged_at_index ON public.audits USING btree (action, logged_at);


--
-- Name: audits_context_logged_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audits_context_logged_at_index ON public.audits USING btree (context, logged_at);


--
-- Name: audits_target_table_target_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audits_target_table_target_id_index ON public.audits USING btree (target_table, target_id);


--
-- Name: audits_target_type_target_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audits_target_type_target_id_index ON public.audits USING btree (target_type, target_id);


--
-- Name: audits_user_id_module_action_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX audits_user_id_module_action_index ON public.audits USING btree (user_id, module, action);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: certificates_user_id_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX certificates_user_id_status_index ON public.certificates USING btree (user_id, status);


--
-- Name: content_reads_readable_type_readable_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX content_reads_readable_type_readable_id_index ON public.content_reads USING btree (readable_type, readable_id);


--
-- Name: content_revisions_content_type_content_id_created_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX content_revisions_content_type_content_id_created_at_index ON public.content_revisions USING btree (content_type, content_id, created_at);


--
-- Name: content_workflow_history_content_type_content_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX content_workflow_history_content_type_content_id_index ON public.content_workflow_history USING btree (content_type, content_id);


--
-- Name: content_workflow_history_created_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX content_workflow_history_created_at_index ON public.content_workflow_history USING btree (created_at);


--
-- Name: content_workflow_history_user_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX content_workflow_history_user_id_index ON public.content_workflow_history USING btree (user_id);


--
-- Name: course_admins_course_id_user_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX course_admins_course_id_user_id_index ON public.course_admins USING btree (course_id, user_id);


--
-- Name: course_outcomes_course_id_order_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX course_outcomes_course_id_order_index ON public.course_outcomes USING btree (course_id, "order");


--
-- Name: course_progress_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX course_progress_status_index ON public.course_progress USING btree (status);


--
-- Name: courses_category_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX courses_category_id_index ON public.courses USING btree (category_id);


--
-- Name: courses_enrollment_type_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX courses_enrollment_type_index ON public.courses USING btree (enrollment_type);


--
-- Name: courses_type_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX courses_type_status_index ON public.courses USING btree (type, status);


--
-- Name: enrollments_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX enrollments_status_index ON public.enrollments USING btree (status);


--
-- Name: forum_statistics_scheme_id_period_start_period_end_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX forum_statistics_scheme_id_period_start_period_end_index ON public.forum_statistics USING btree (scheme_id, period_start, period_end);


--
-- Name: grades_source_type_source_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX grades_source_type_source_id_index ON public.grades USING btree (source_type, source_id);


--
-- Name: grades_user_id_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX grades_user_id_status_index ON public.grades USING btree (user_id, status);


--
-- Name: grading_rubrics_scope_type_scope_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX grading_rubrics_scope_type_scope_id_index ON public.grading_rubrics USING btree (scope_type, scope_id);


--
-- Name: idx_activities_created_at; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_activities_created_at ON public.user_activities USING btree (created_at);


--
-- Name: idx_activities_user_created; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_activities_user_created ON public.user_activities USING btree (user_id, created_at);


--
-- Name: idx_activities_user_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_activities_user_id ON public.user_activities USING btree (user_id);


--
-- Name: idx_admin; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_admin ON public.profile_audit_logs USING btree (admin_id, created_at);


--
-- Name: idx_answers_files_expired_at; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_answers_files_expired_at ON public.answers USING btree (files_expired_at);


--
-- Name: idx_answers_question; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_answers_question ON public.answers USING btree (question_id);


--
-- Name: idx_answers_submission; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_answers_submission ON public.answers USING btree (submission_id);


--
-- Name: idx_assignment_questions_assignment; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_assignment_questions_assignment ON public.assignment_questions USING btree (assignment_id);


--
-- Name: idx_assignment_questions_order; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_assignment_questions_order ON public.assignment_questions USING btree ("order");


--
-- Name: idx_assignment_questions_type; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_assignment_questions_type ON public.assignment_questions USING btree (type);


--
-- Name: idx_assignments_assignable; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_assignments_assignable ON public.assignments USING btree (assignable_type, assignable_id);


--
-- Name: idx_assignments_deadline; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_assignments_deadline ON public.assignments USING btree (deadline_at);


--
-- Name: idx_audit_logs_action; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_audit_logs_action ON public.audit_logs USING btree (action);


--
-- Name: idx_audit_logs_actor; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_audit_logs_actor ON public.audit_logs USING btree (actor_id, actor_type);


--
-- Name: idx_audit_logs_created_at; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_audit_logs_created_at ON public.audit_logs USING btree (created_at);


--
-- Name: idx_audit_logs_subject; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_audit_logs_subject ON public.audit_logs USING btree (subject_id, subject_type);


--
-- Name: idx_courses_category; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_courses_category ON public.courses USING btree (category_id);


--
-- Name: idx_courses_category_status; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_courses_category_status ON public.courses USING btree (category_id, status);


--
-- Name: idx_courses_instructor; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_courses_instructor ON public.courses USING btree (instructor_id);


--
-- Name: idx_courses_listing; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_courses_listing ON public.courses USING btree (status, deleted_at, published_at);


--
-- Name: idx_enrollments_user_course_status; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_enrollments_user_course_status ON public.enrollments USING btree (user_id, course_id, status);


--
-- Name: idx_grades_grader; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_grades_grader ON public.grades USING btree (graded_by);


--
-- Name: idx_grades_released; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_grades_released ON public.grades USING btree (released_at);


--
-- Name: idx_grades_submission; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_grades_submission ON public.grades USING btree (submission_id);


--
-- Name: idx_jwt_device_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_jwt_device_id ON public.jwt_refresh_tokens USING btree (device_id);


--
-- Name: idx_jwt_expires_at; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_jwt_expires_at ON public.jwt_refresh_tokens USING btree (expires_at);


--
-- Name: idx_jwt_user_device; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_jwt_user_device ON public.jwt_refresh_tokens USING btree (user_id, device_id);


--
-- Name: idx_jwt_user_expires; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_jwt_user_expires ON public.jwt_refresh_tokens USING btree (user_id, expires_at);


--
-- Name: idx_jwt_user_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_jwt_user_id ON public.jwt_refresh_tokens USING btree (user_id);


--
-- Name: idx_model_perms_model_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_model_perms_model_id ON public.model_has_permissions USING btree (model_id);


--
-- Name: idx_model_perms_model_perm; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_model_perms_model_perm ON public.model_has_permissions USING btree (model_id, permission_id);


--
-- Name: idx_model_perms_permission_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_model_perms_permission_id ON public.model_has_permissions USING btree (permission_id);


--
-- Name: idx_model_roles_model_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_model_roles_model_id ON public.model_has_roles USING btree (model_id);


--
-- Name: idx_model_roles_model_role; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_model_roles_model_role ON public.model_has_roles USING btree (model_id, role_id);


--
-- Name: idx_model_roles_role_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_model_roles_role_id ON public.model_has_roles USING btree (role_id);


--
-- Name: idx_overrides_assignment; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_overrides_assignment ON public.overrides USING btree (assignment_id);


--
-- Name: idx_overrides_assignment_student_type; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_overrides_assignment_student_type ON public.overrides USING btree (assignment_id, student_id, type);


--
-- Name: idx_overrides_expires; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_overrides_expires ON public.overrides USING btree (expires_at);


--
-- Name: idx_overrides_grantor; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_overrides_grantor ON public.overrides USING btree (grantor_id);


--
-- Name: idx_overrides_student; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_overrides_student ON public.overrides USING btree (student_id);


--
-- Name: idx_overrides_type; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_overrides_type ON public.overrides USING btree (type);


--
-- Name: idx_prereq_assignment; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_prereq_assignment ON public.assignment_prerequisites USING btree (assignment_id);


--
-- Name: idx_prereq_prerequisite; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_prereq_prerequisite ON public.assignment_prerequisites USING btree (prerequisite_id);


--
-- Name: idx_related; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_related ON public.user_activities USING btree (related_type, related_id);


--
-- Name: idx_submissions_assignment_user_attempt; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_assignment_user_attempt ON public.submissions USING btree (assignment_id, user_id, attempt_number);


--
-- Name: idx_submissions_score; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_score ON public.submissions USING btree (score);


--
-- Name: idx_submissions_state; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_state ON public.submissions USING btree (state);


--
-- Name: idx_submissions_status_submitted; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_status_submitted ON public.submissions USING btree (status, submitted_at);


--
-- Name: idx_submissions_student_assignment; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_student_assignment ON public.submissions USING btree (user_id, assignment_id);


--
-- Name: idx_submissions_submitted_at; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_submitted_at ON public.submissions USING btree (submitted_at);


--
-- Name: idx_submissions_time_expired; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_submissions_time_expired ON public.submissions USING btree (time_expired_at);


--
-- Name: idx_units_course_id; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_units_course_id ON public.units USING btree (course_id);


--
-- Name: idx_units_course_order; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_units_course_order ON public.units USING btree (course_id, "order");


--
-- Name: idx_user_action; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_user_action ON public.profile_audit_logs USING btree (user_id, action, created_at);


--
-- Name: idx_user_order; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_user_order ON public.pinned_badges USING btree (user_id, "order");


--
-- Name: idx_user_type; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_user_type ON public.user_activities USING btree (user_id, activity_type, created_at);


--
-- Name: idx_users_created_at; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_users_created_at ON public.users USING btree (created_at);


--
-- Name: idx_users_email; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_users_email ON public.users USING btree (email);


--
-- Name: idx_users_email_status; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_users_email_status ON public.users USING btree (email, status);


--
-- Name: idx_users_status; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_users_status ON public.users USING btree (status);


--
-- Name: idx_users_status_created; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_users_status_created ON public.users USING btree (status, created_at);


--
-- Name: idx_users_username; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX idx_users_username ON public.users USING btree (username);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: jwt_refresh_tokens_user_id_device_id_revoked_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX jwt_refresh_tokens_user_id_device_id_revoked_at_index ON public.jwt_refresh_tokens USING btree (user_id, device_id, revoked_at);


--
-- Name: jwt_refresh_tokens_user_id_replaced_by_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX jwt_refresh_tokens_user_id_replaced_by_index ON public.jwt_refresh_tokens USING btree (user_id, replaced_by);


--
-- Name: jwt_refresh_tokens_user_id_revoked_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX jwt_refresh_tokens_user_id_revoked_at_index ON public.jwt_refresh_tokens USING btree (user_id, revoked_at);


--
-- Name: leaderboards_user_course_idx; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX leaderboards_user_course_idx ON public.leaderboards USING btree (user_id, course_id);


--
-- Name: learning_streaks_activity_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX learning_streaks_activity_date_index ON public.learning_streaks USING btree (activity_date);


--
-- Name: learning_streaks_user_id_activity_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX learning_streaks_user_id_activity_date_index ON public.learning_streaks USING btree (user_id, activity_date);


--
-- Name: lesson_blocks_lesson_id_order_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX lesson_blocks_lesson_id_order_index ON public.lesson_blocks USING btree (lesson_id, "order");


--
-- Name: lesson_progress_status_attempt_count_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX lesson_progress_status_attempt_count_index ON public.lesson_progress USING btree (status, attempt_count);


--
-- Name: lessons_unit_id_order_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX lessons_unit_id_order_index ON public.lessons USING btree (unit_id, "order");


--
-- Name: levels_user_course_idx; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX levels_user_course_idx ON public.levels USING btree (user_id, course_id);


--
-- Name: master_data_type_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX master_data_type_index ON public.master_data USING btree (type);


--
-- Name: master_data_type_is_active_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX master_data_type_is_active_index ON public.master_data USING btree (type, is_active);


--
-- Name: media_model_type_model_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX media_model_type_model_id_index ON public.media USING btree (model_type, model_id);


--
-- Name: media_order_column_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX media_order_column_index ON public.media USING btree (order_column);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: news_is_featured_published_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX news_is_featured_published_at_index ON public.news USING btree (is_featured, published_at);


--
-- Name: news_status_published_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX news_status_published_at_index ON public.news USING btree (status, published_at);


--
-- Name: news_title_excerpt_content_fulltext; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX news_title_excerpt_content_fulltext ON public.news USING gin ((((to_tsvector('english'::regconfig, (title)::text) || to_tsvector('english'::regconfig, excerpt)) || to_tsvector('english'::regconfig, content))));


--
-- Name: notification_preferences_user_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX notification_preferences_user_id_index ON public.notification_preferences USING btree (user_id);


--
-- Name: notifications_type_channel_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX notifications_type_channel_index ON public.notifications USING btree (type, channel);


--
-- Name: otp_codes_user_id_purpose_expires_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX otp_codes_user_id_purpose_expires_at_index ON public.otp_codes USING btree (user_id, purpose, expires_at);


--
-- Name: otp_codes_uuid_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX otp_codes_uuid_index ON public.otp_codes USING btree (uuid);


--
-- Name: points_user_id_source_type_reason_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX points_user_id_source_type_reason_index ON public.points USING btree (user_id, source_type, reason);


--
-- Name: reactions_reactable_type_reactable_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX reactions_reactable_type_reactable_id_index ON public.reactions USING btree (reactable_type, reactable_id);


--
-- Name: replies_parent_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX replies_parent_id_index ON public.replies USING btree (parent_id);


--
-- Name: replies_thread_id_created_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX replies_thread_id_created_at_index ON public.replies USING btree (thread_id, created_at);


--
-- Name: replies_thread_id_is_accepted_answer_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX replies_thread_id_is_accepted_answer_index ON public.replies USING btree (thread_id, is_accepted_answer);


--
-- Name: reports_type_generated_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX reports_type_generated_at_index ON public.reports USING btree (type, generated_at);


--
-- Name: search_history_query_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX search_history_query_index ON public.search_history USING btree (query);


--
-- Name: search_history_user_id_created_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX search_history_user_id_created_at_index ON public.search_history USING btree (user_id, created_at);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: submissions_status_submitted_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX submissions_status_submitted_at_index ON public.submissions USING btree (status, submitted_at);


--
-- Name: taggables_taggable_type_taggable_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX taggables_taggable_type_taggable_id_index ON public.taggables USING btree (taggable_type, taggable_id);


--
-- Name: telescope_entries_batch_id_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX telescope_entries_batch_id_index ON public.telescope_entries USING btree (batch_id);


--
-- Name: telescope_entries_created_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX telescope_entries_created_at_index ON public.telescope_entries USING btree (created_at);


--
-- Name: telescope_entries_family_hash_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX telescope_entries_family_hash_index ON public.telescope_entries USING btree (family_hash);


--
-- Name: telescope_entries_tags_tag_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX telescope_entries_tags_tag_index ON public.telescope_entries_tags USING btree (tag);


--
-- Name: telescope_entries_type_should_display_on_index_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX telescope_entries_type_should_display_on_index_index ON public.telescope_entries USING btree (type, should_display_on_index);


--
-- Name: threads_is_pinned_last_activity_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX threads_is_pinned_last_activity_at_index ON public.threads USING btree (is_pinned, last_activity_at);


--
-- Name: threads_scheme_id_last_activity_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX threads_scheme_id_last_activity_at_index ON public.threads USING btree (scheme_id, last_activity_at);


--
-- Name: threads_title_content_fulltext; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX threads_title_content_fulltext ON public.threads USING gin (((to_tsvector('english'::regconfig, (title)::text) || to_tsvector('english'::regconfig, content))));


--
-- Name: unit_progress_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX unit_progress_status_index ON public.unit_progress USING btree (status);


--
-- Name: units_course_id_order_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX units_course_id_order_index ON public.units USING btree (course_id, "order");


--
-- Name: user_challenge_assignments_challenge_id_assigned_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_assignments_challenge_id_assigned_date_index ON public.user_challenge_assignments USING btree (challenge_id, assigned_date);


--
-- Name: user_challenge_assignments_expires_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_assignments_expires_at_index ON public.user_challenge_assignments USING btree (expires_at);


--
-- Name: user_challenge_assignments_status_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_assignments_status_index ON public.user_challenge_assignments USING btree (status);


--
-- Name: user_challenge_assignments_user_id_assigned_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_assignments_user_id_assigned_date_index ON public.user_challenge_assignments USING btree (user_id, assigned_date);


--
-- Name: user_challenge_completions_challenge_id_completed_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_completions_challenge_id_completed_date_index ON public.user_challenge_completions USING btree (challenge_id, completed_date);


--
-- Name: user_challenge_completions_completed_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_completions_completed_date_index ON public.user_challenge_completions USING btree (completed_date);


--
-- Name: user_challenge_completions_user_id_completed_date_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_challenge_completions_user_id_completed_date_index ON public.user_challenge_completions USING btree (user_id, completed_date);


--
-- Name: user_gamification_stats_current_streak_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_gamification_stats_current_streak_index ON public.user_gamification_stats USING btree (current_streak);


--
-- Name: user_gamification_stats_global_level_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_gamification_stats_global_level_index ON public.user_gamification_stats USING btree (global_level);


--
-- Name: user_gamification_stats_global_level_total_xp_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_gamification_stats_global_level_total_xp_index ON public.user_gamification_stats USING btree (global_level, total_xp);


--
-- Name: user_gamification_stats_total_points_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_gamification_stats_total_points_index ON public.user_gamification_stats USING btree (total_points);


--
-- Name: user_gamification_stats_total_xp_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX user_gamification_stats_total_xp_index ON public.user_gamification_stats USING btree (total_xp);


--
-- Name: users_status_created_at_index; Type: INDEX; Schema: public; Owner: darrielmarkerizal
--

CREATE INDEX users_status_created_at_index ON public.users USING btree (status, created_at);


--
-- Name: announcements announcements_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: announcements announcements_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: announcements announcements_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: answers answers_question_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.answers
    ADD CONSTRAINT answers_question_id_foreign FOREIGN KEY (question_id) REFERENCES public.assignment_questions(id) ON DELETE CASCADE;


--
-- Name: answers answers_submission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.answers
    ADD CONSTRAINT answers_submission_id_foreign FOREIGN KEY (submission_id) REFERENCES public.submissions(id) ON DELETE CASCADE;


--
-- Name: appeals appeals_reviewer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.appeals
    ADD CONSTRAINT appeals_reviewer_id_foreign FOREIGN KEY (reviewer_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: appeals appeals_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.appeals
    ADD CONSTRAINT appeals_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: appeals appeals_submission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.appeals
    ADD CONSTRAINT appeals_submission_id_foreign FOREIGN KEY (submission_id) REFERENCES public.submissions(id) ON DELETE CASCADE;


--
-- Name: assignment_prerequisites assignment_prerequisites_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_prerequisites
    ADD CONSTRAINT assignment_prerequisites_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id) ON DELETE CASCADE;


--
-- Name: assignment_prerequisites assignment_prerequisites_prerequisite_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_prerequisites
    ADD CONSTRAINT assignment_prerequisites_prerequisite_id_foreign FOREIGN KEY (prerequisite_id) REFERENCES public.assignments(id) ON DELETE CASCADE;


--
-- Name: assignment_questions assignment_questions_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignment_questions
    ADD CONSTRAINT assignment_questions_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id) ON DELETE CASCADE;


--
-- Name: assignments assignments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: assignments assignments_lesson_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_lesson_id_foreign FOREIGN KEY (lesson_id) REFERENCES public.lessons(id) ON DELETE CASCADE;


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: audits audits_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: certificates certificates_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: certificates certificates_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: challenges challenges_badge_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.challenges
    ADD CONSTRAINT challenges_badge_id_foreign FOREIGN KEY (badge_id) REFERENCES public.badges(id) ON DELETE SET NULL;


--
-- Name: content_reads content_reads_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_reads
    ADD CONSTRAINT content_reads_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: content_revisions content_revisions_editor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_revisions
    ADD CONSTRAINT content_revisions_editor_id_foreign FOREIGN KEY (editor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: content_workflow_history content_workflow_history_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.content_workflow_history
    ADD CONSTRAINT content_workflow_history_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: course_admins course_admins_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_admins
    ADD CONSTRAINT course_admins_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: course_admins course_admins_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_admins
    ADD CONSTRAINT course_admins_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: course_outcomes course_outcomes_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_outcomes
    ADD CONSTRAINT course_outcomes_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: course_progress course_progress_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_progress
    ADD CONSTRAINT course_progress_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES public.enrollments(id) ON DELETE CASCADE;


--
-- Name: course_tag_pivot course_tag_pivot_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_tag_pivot
    ADD CONSTRAINT course_tag_pivot_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: course_tag_pivot course_tag_pivot_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.course_tag_pivot
    ADD CONSTRAINT course_tag_pivot_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: courses courses_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: courses courses_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: courses courses_instructor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.courses
    ADD CONSTRAINT courses_instructor_id_foreign FOREIGN KEY (instructor_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: enrollments enrollments_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.enrollments
    ADD CONSTRAINT enrollments_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: enrollments enrollments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.enrollments
    ADD CONSTRAINT enrollments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forum_statistics forum_statistics_scheme_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.forum_statistics
    ADD CONSTRAINT forum_statistics_scheme_id_foreign FOREIGN KEY (scheme_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: forum_statistics forum_statistics_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.forum_statistics
    ADD CONSTRAINT forum_statistics_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: grade_reviews grade_reviews_grade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grade_reviews
    ADD CONSTRAINT grade_reviews_grade_id_foreign FOREIGN KEY (grade_id) REFERENCES public.grades(id) ON DELETE CASCADE;


--
-- Name: grade_reviews grade_reviews_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grade_reviews
    ADD CONSTRAINT grade_reviews_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: grade_reviews grade_reviews_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grade_reviews
    ADD CONSTRAINT grade_reviews_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: grades grades_graded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grades
    ADD CONSTRAINT grades_graded_by_foreign FOREIGN KEY (graded_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: grades grades_submission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grades
    ADD CONSTRAINT grades_submission_id_foreign FOREIGN KEY (submission_id) REFERENCES public.submissions(id) ON DELETE CASCADE;


--
-- Name: grades grades_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.grades
    ADD CONSTRAINT grades_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: jwt_refresh_tokens jwt_refresh_tokens_replaced_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jwt_refresh_tokens
    ADD CONSTRAINT jwt_refresh_tokens_replaced_by_foreign FOREIGN KEY (replaced_by) REFERENCES public.jwt_refresh_tokens(id) ON DELETE SET NULL;


--
-- Name: jwt_refresh_tokens jwt_refresh_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.jwt_refresh_tokens
    ADD CONSTRAINT jwt_refresh_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: leaderboards leaderboards_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.leaderboards
    ADD CONSTRAINT leaderboards_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: leaderboards leaderboards_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.leaderboards
    ADD CONSTRAINT leaderboards_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: learning_streaks learning_streaks_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.learning_streaks
    ADD CONSTRAINT learning_streaks_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: lesson_blocks lesson_blocks_lesson_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_blocks
    ADD CONSTRAINT lesson_blocks_lesson_id_foreign FOREIGN KEY (lesson_id) REFERENCES public.lessons(id) ON DELETE CASCADE;


--
-- Name: lesson_progress lesson_progress_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_progress
    ADD CONSTRAINT lesson_progress_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES public.enrollments(id) ON DELETE CASCADE;


--
-- Name: lesson_progress lesson_progress_lesson_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lesson_progress
    ADD CONSTRAINT lesson_progress_lesson_id_foreign FOREIGN KEY (lesson_id) REFERENCES public.lessons(id) ON DELETE CASCADE;


--
-- Name: lessons lessons_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.lessons
    ADD CONSTRAINT lessons_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE CASCADE;


--
-- Name: levels levels_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: levels levels_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: login_activities login_activities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.login_activities
    ADD CONSTRAINT login_activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: news news_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: news_category news_category_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news_category
    ADD CONSTRAINT news_category_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.content_categories(id) ON DELETE CASCADE;


--
-- Name: news_category news_category_news_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news_category
    ADD CONSTRAINT news_category_news_id_foreign FOREIGN KEY (news_id) REFERENCES public.news(id) ON DELETE CASCADE;


--
-- Name: news news_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.news
    ADD CONSTRAINT news_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: notification_preferences notification_preferences_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT notification_preferences_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: otp_codes otp_codes_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.otp_codes
    ADD CONSTRAINT otp_codes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: overrides overrides_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.overrides
    ADD CONSTRAINT overrides_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id) ON DELETE CASCADE;


--
-- Name: overrides overrides_grantor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.overrides
    ADD CONSTRAINT overrides_grantor_id_foreign FOREIGN KEY (grantor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: overrides overrides_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.overrides
    ADD CONSTRAINT overrides_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: pinned_badges pinned_badges_badge_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.pinned_badges
    ADD CONSTRAINT pinned_badges_badge_id_foreign FOREIGN KEY (badge_id) REFERENCES public.badges(id) ON DELETE CASCADE;


--
-- Name: pinned_badges pinned_badges_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.pinned_badges
    ADD CONSTRAINT pinned_badges_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: points points_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.points
    ADD CONSTRAINT points_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: profile_audit_logs profile_audit_logs_admin_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_audit_logs
    ADD CONSTRAINT profile_audit_logs_admin_id_foreign FOREIGN KEY (admin_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: profile_audit_logs profile_audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_audit_logs
    ADD CONSTRAINT profile_audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: profile_privacy_settings profile_privacy_settings_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.profile_privacy_settings
    ADD CONSTRAINT profile_privacy_settings_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reactions reactions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reactions
    ADD CONSTRAINT reactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: replies replies_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.replies
    ADD CONSTRAINT replies_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: replies replies_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.replies
    ADD CONSTRAINT replies_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: replies replies_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.replies
    ADD CONSTRAINT replies_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.replies(id) ON DELETE CASCADE;


--
-- Name: replies replies_thread_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.replies
    ADD CONSTRAINT replies_thread_id_foreign FOREIGN KEY (thread_id) REFERENCES public.threads(id) ON DELETE CASCADE;


--
-- Name: reports reports_generated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_generated_by_foreign FOREIGN KEY (generated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: search_history search_history_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.search_history
    ADD CONSTRAINT search_history_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: social_accounts social_accounts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.social_accounts
    ADD CONSTRAINT social_accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: submission_files submission_files_submission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submission_files
    ADD CONSTRAINT submission_files_submission_id_foreign FOREIGN KEY (submission_id) REFERENCES public.submissions(id) ON DELETE CASCADE;


--
-- Name: submissions submissions_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submissions
    ADD CONSTRAINT submissions_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id) ON DELETE CASCADE;


--
-- Name: submissions submissions_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submissions
    ADD CONSTRAINT submissions_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES public.enrollments(id) ON DELETE SET NULL;


--
-- Name: submissions submissions_previous_submission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submissions
    ADD CONSTRAINT submissions_previous_submission_id_foreign FOREIGN KEY (previous_submission_id) REFERENCES public.submissions(id) ON DELETE SET NULL;


--
-- Name: submissions submissions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.submissions
    ADD CONSTRAINT submissions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: taggables taggables_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: telescope_entries_tags telescope_entries_tags_entry_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_entry_uuid_foreign FOREIGN KEY (entry_uuid) REFERENCES public.telescope_entries(uuid) ON DELETE CASCADE;


--
-- Name: threads threads_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.threads
    ADD CONSTRAINT threads_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: threads threads_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.threads
    ADD CONSTRAINT threads_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: threads threads_scheme_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.threads
    ADD CONSTRAINT threads_scheme_id_foreign FOREIGN KEY (scheme_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: unit_progress unit_progress_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.unit_progress
    ADD CONSTRAINT unit_progress_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES public.enrollments(id) ON DELETE CASCADE;


--
-- Name: unit_progress unit_progress_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.unit_progress
    ADD CONSTRAINT unit_progress_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE CASCADE;


--
-- Name: units units_course_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_course_id_foreign FOREIGN KEY (course_id) REFERENCES public.courses(id) ON DELETE CASCADE;


--
-- Name: user_activities user_activities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_activities
    ADD CONSTRAINT user_activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_badges user_badges_badge_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_badges
    ADD CONSTRAINT user_badges_badge_id_foreign FOREIGN KEY (badge_id) REFERENCES public.badges(id) ON DELETE CASCADE;


--
-- Name: user_badges user_badges_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_badges
    ADD CONSTRAINT user_badges_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_challenge_assignments user_challenge_assignments_challenge_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_assignments
    ADD CONSTRAINT user_challenge_assignments_challenge_id_foreign FOREIGN KEY (challenge_id) REFERENCES public.challenges(id) ON DELETE CASCADE;


--
-- Name: user_challenge_assignments user_challenge_assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_assignments
    ADD CONSTRAINT user_challenge_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_challenge_completions user_challenge_completions_challenge_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_completions
    ADD CONSTRAINT user_challenge_completions_challenge_id_foreign FOREIGN KEY (challenge_id) REFERENCES public.challenges(id) ON DELETE CASCADE;


--
-- Name: user_challenge_completions user_challenge_completions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_challenge_completions
    ADD CONSTRAINT user_challenge_completions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_gamification_stats user_gamification_stats_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_gamification_stats
    ADD CONSTRAINT user_gamification_stats_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_notifications user_notifications_notification_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_notifications
    ADD CONSTRAINT user_notifications_notification_id_foreign FOREIGN KEY (notification_id) REFERENCES public.notifications(id) ON DELETE CASCADE;


--
-- Name: user_notifications user_notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: darrielmarkerizal
--

ALTER TABLE ONLY public.user_notifications
    ADD CONSTRAINT user_notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict xW1RxwGt64QKjyGORJgwEuPNO7KcgtdlAfuwXNCdNRQCtuU5FzkXlPtlJjM1Wfo

