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
};

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
    participant_limit: number | null;
    is_competitive: boolean;
    certification: string | null;
    awards: string | null;
    route_url: string | null;
    registration_url: string | null;
    status: string;
    is_main: boolean;
    prices: RacePrice[];
};

export type EventLink = {
    type: LinkType;
    url: string;
    label: string | null;
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
    links: EventLink[];
};
