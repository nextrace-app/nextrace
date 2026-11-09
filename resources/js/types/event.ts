export type EventStatus = 'scheduled' | 'postponed' | 'cancelled' | 'completed';

export type DistanceClass =
    | '5k'
    | '10k'
    | 'half_marathon'
    | 'marathon'
    | 'ultra'
    | 'kids'
    | 'other';

export type RegistrationStatus =
    | 'unknown'
    | 'not_open'
    | 'open'
    | 'closed'
    | 'sold_out';

export type LinkType =
    | 'website'
    | 'facebook'
    | 'instagram'
    | 'regulations'
    | 'route'
    | 'results'
    | 'photos'
    | 'timing'
    | 'other';

export type RacePrice = {
    label: string | null;
    amount: number;
    currency: string;
    valid_from: string | null;
    valid_until: string | null;
    /** 'past' when over, 'current' for the tier valid today, null otherwise. */
    state: 'past' | 'current' | null;
};

/** Where a registration stands today, worked out on the server. */
export type RegistrationState =
    | 'open'
    | 'free'
    | 'sold_out'
    | 'closed'
    | 'not_open'
    | 'unknown';

export type Race = {
    id: number;
    slug: string;
    name: string;
    discipline: string;
    terrain: string | null;
    distance_class: DistanceClass | null;
    distance: string;
    distance_meters: number;
    elevation_gain_m: number | null;
    team_size: number | null;
    date: string;
    time: string | null;
    start_location: string | null;
    finish_location: string | null;
    time_limit_minutes: number | null;
    min_age: number | null;
    aid_stations: number | null;
    medical_certificate_required: boolean;
    participant_limit: number | null;
    is_competitive: boolean;
    certification: string | null;
    awards: string | null;
    route_url: string | null;
    registration_url: string | null;
    status: string;
    is_main: boolean;
    sold_out: boolean;
    registration_state: RegistrationState;
    /** The last day of the current price when a dearer tier follows within a week. */
    price_rises_after: string | null;
    prices: RacePrice[];
    cutoffs: Cutoff[];
};

export type EventLink = {
    type: LinkType;
    url: string;
    label: string | null;
};

export type KeyDateType =
    | 'registration_opens'
    | 'registration_closes'
    | 'changes_deadline'
    | 'expo'
    | 'bib_pickup'
    | 'other';

/** One entry of the timeline of important dates of an event. */
export type KeyDate = {
    type: KeyDateType;
    from: string;
    /** The last day of a span; null for a single day. */
    to: string | null;
    /** Null when the type says what the date is. */
    label: string | null;
    state: 'past' | 'next' | 'upcoming';
};

export type SectionType =
    | 'transport'
    | 'bag_drop'
    | 'expo'
    | 'road_closures'
    | 'medical'
    | 'other';

/** One section of the practical information of an event. */
export type EventSection = {
    type: SectionType;
    /** Null when the type names the section. */
    title: string | null;
    /** Markdown rendered to safe HTML on the server. */
    body_html: string;
};

/** One row of the cut-off table of a race. */
export type Cutoff = {
    distance_meters: number;
    /** Wall-clock moment, "Y-m-d H:i". */
    closes_at: string;
    is_finish: boolean;
};

export type Event = {
    id: number;
    slug: string;
    name: string;
    description: string;
    date: string;
    time: string | null;
    city: string;
    location: string;
    category: string;
    distance: string;
    distance_meters: number;
    participants: number | null;
    image: string | null;
    latitude: number | null;
    longitude: number | null;
    registration_url: string | null;
    edition_number: number | null;
    ends_on: string | null;
    /** The days the event takes; "to" is null for a one-day event. */
    dates: { from: string; to: string | null };
    status: EventStatus;
    date_confirmed: boolean;
    venue: string | null;
    registration: {
        status: RegistrationStatus;
        opens_at: string | null;
        closes_at: string | null;
        url: string | null;
    };
    contact: {
        email: string | null;
        phone: string | null;
    };
    series: { slug: string; name: string } | null;
    organizer: {
        name: string;
        email: string | null;
        phone: string | null;
        website: string | null;
    } | null;
    region: { slug: string; name: string } | null;
    races: Race[];
    registration_state: {
        state: RegistrationState;
        price_rises_soon: boolean;
    };
    key_dates: KeyDate[];
    sections: EventSection[];
    links: EventLink[];
};
