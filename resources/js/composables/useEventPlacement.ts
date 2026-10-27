import type { Event } from '@/types';

/**
 * Where an event sits on the stylised island map. The map is a plain 250 x 90
 * canvas drawn from longitude and latitude, so pins come from the event's own
 * coordinates, or from the middle of its prefecture when there are none.
 */
export const mapWidth = 250;
export const mapHeight = 90;

const regionCentres: Record<string, [number, number]> = {
    heraklion: [35.28, 25.08],
    chania: [35.36, 23.95],
    rethymno: [35.3, 24.5],
    lasithi: [35.15, 25.8],
};

export const islandOutline: [number, number][] = [
    [23.52, 35.3],
    [23.55, 35.52],
    [23.72, 35.6],
    [23.9, 35.55],
    [24.1, 35.52],
    [24.3, 35.46],
    [24.55, 35.4],
    [24.8, 35.4],
    [25.0, 35.4],
    [25.2, 35.36],
    [25.45, 35.33],
    [25.7, 35.3],
    [25.95, 35.34],
    [26.15, 35.3],
    [26.28, 35.2],
    [26.1, 35.03],
    [25.8, 34.98],
    [25.45, 34.97],
    [25.1, 34.98],
    [24.8, 34.98],
    [24.5, 35.08],
    [24.2, 35.18],
    [23.9, 35.2],
    [23.65, 35.18],
];

export function project(latitude: number, longitude: number): Point {
    return {
        x: Math.round((longitude - 23.4) * 0.82 * 100),
        y: Math.round((35.75 - latitude) * 100),
    };
}

export type Point = { x: number; y: number };

export function placeEvent(event: Event): Point | null {
    if (event.latitude !== null && event.longitude !== null) {
        return project(event.latitude, event.longitude);
    }

    const centre = event.region ? regionCentres[event.region.slug] : undefined;

    return centre ? project(centre[0], centre[1]) : null;
}

/** A closed, smoothed SVG path through the given points. */
export function smoothPath(points: Point[]): string {
    const count = points.length;
    let path = `M${points[0].x} ${points[0].y}`;

    for (let i = 0; i < count; i++) {
        const a = points[(i - 1 + count) % count];
        const b = points[i];
        const c = points[(i + 1) % count];
        const d = points[(i + 2) % count];

        path += `C${(b.x + (c.x - a.x) / 6).toFixed(1)} ${(b.y + (c.y - a.y) / 6).toFixed(1)} ${(c.x - (d.x - b.x) / 6).toFixed(1)} ${(c.y - (d.y - b.y) / 6).toFixed(1)} ${c.x} ${c.y}`;
    }

    return `${path}Z`;
}

export const islandPath = smoothPath(
    islandOutline.map(([longitude, latitude]) => project(latitude, longitude)),
);
