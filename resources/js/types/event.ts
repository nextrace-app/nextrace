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
};
