<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Building2,
    Calendar,
    Camera,
    Clock,
    ExternalLink,
    FileText,
    Flag,
    Globe,
    Link2,
    Mail,
    Map,
    MapPin,
    Phone,
    Route,
    Ticket,
    Timer,
    Trophy,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import EventCountdown from '@/components/EventCountdown.vue';
import MoreRaces from '@/components/MoreRaces.vue';
import PracticalInfo from '@/components/PracticalInfo.vue';
import RaceCourse from '@/components/RaceCourse.vue';
import RaceCutoffs from '@/components/RaceCutoffs.vue';
import RaceNews from '@/components/RaceNews.vue';
import RaceResults from '@/components/RaceResults.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type {
    Event,
    EventCountdown as Countdown,
    EventEdition,
    LinkType,
    Post,
} from '@/types';

const props = defineProps<{
    event: Event;
    news: { posts: Post[]; total: number };
    editions: EventEdition[];
    similarEvents: Event[];
    organizerEvents: Event[];
    favorites: number[];
    countdown: Countdown | null;
    isFavorite: boolean;
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);

/** The links that belong in "Results and past editions", not in the facts. */
const resultLinkTypes: LinkType[] = ['results', 'photos', 'certificate'];
const resultLinks = computed(() =>
    props.event.links.filter((link) => resultLinkTypes.includes(link.type)),
);
const factLinks = computed(() =>
    props.event.links.filter((link) => !resultLinkTypes.includes(link.type)),
);

/** The races that have a course to draw, from their GPX files. */
const courseRaces = computed(() =>
    props.event.races.filter((race) => race.course !== null),
);
const { localeRoute } = useLocale();
const {
    formatDate,
    formatDateRange,
    formatDateTimeString,
    formatDistance,
    formatDuration,
    formatNumber,
    formatPrice,
    formatTime,
    categoryLabel,
    mapUrl,
} = useEventFormat();

const fullDate = computed(() =>
    props.event.dates.to
        ? formatDateRange(props.event.dates.from, props.event.dates.to)
        : formatDate(props.event.dates.from, {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          }),
);
const map = computed(() => mapUrl(props.event));

const banner = computed(() => {
    if (props.event.status === 'cancelled') {
        return 'banner_cancelled';
    }

    if (props.event.status === 'postponed') {
        return 'banner_postponed';
    }

    return props.event.date_confirmed ? null : 'banner_unconfirmed';
});

const linkIcons: Record<LinkType, Component> = {
    website: Globe,
    facebook: Link2,
    instagram: Camera,
    regulations: FileText,
    route: Route,
    results: Trophy,
    photos: Camera,
    certificate: FileText,
    timing: Timer,
    other: Link2,
};

function raceStart(race: Event['races'][number]): string {
    const date = formatDate(race.date, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });

    return race.time ? `${date}, ${formatTime(race.date, race.time)}` : date;
}

function priceRange(price: Event['races'][number]['prices'][number]): string {
    if (price.valid_from && price.valid_until) {
        return formatDateRange(price.valid_from, price.valid_until);
    }

    if (price.valid_until) {
        return t('events.races.price_until', {
            date: formatDate(price.valid_until),
        });
    }

    return price.valid_from
        ? t('events.races.price_from', { date: formatDate(price.valid_from) })
        : '';
}

function registrationDate(value: string): string {
    return formatDateTimeString(value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const facts = computed(() => {
    const items: {
        key: string;
        icon: Component;
        label: string;
        value: string;
        datetime?: string;
    }[] = [
        {
            key: 'date',
            icon: Calendar,
            label: t('events.fields.date'),
            value: fullDate.value,
            datetime: props.event.dates.from,
        },
    ];

    if (!props.event.dates.to) {
        items.push({
            key: 'time',
            icon: Clock,
            label: t('events.fields.time'),
            value: props.event.time ?? t('events.time_tba'),
        });
    }

    items.push(
        {
            key: 'location',
            icon: MapPin,
            label: t('events.fields.location'),
            value: props.event.location,
        },
        {
            key: 'distance',
            icon: Route,
            label: t('events.fields.distance'),
            value: formatDistance(props.event.distance_meters),
        },
    );

    if (props.event.participants) {
        items.push({
            key: 'participants',
            icon: Users,
            label: t('events.participants'),
            value: formatNumber(props.event.participants),
        });
    }

    return items;
});

/**
 * What the sidebar says next to a race: its state when it cannot be entered,
 * otherwise the price valid today, or the cheapest one when none is.
 */
function sidebarPrice(
    race: Event['races'][number],
): { text: string; muted: boolean } | null {
    if (['sold_out', 'closed', 'not_open'].includes(race.registration_state)) {
        return {
            text: t(`events.registration_status.${race.registration_state}`),
            muted: true,
        };
    }

    const price =
        race.prices.find((tier) => tier.state === 'current') ??
        (race.prices.length > 0
            ? race.prices.reduce((least, tier) =>
                  tier.amount < least.amount ? tier : least,
              )
            : null);

    if (!price) {
        return null;
    }

    return {
        text:
            price.amount === 0
                ? t('events.races.free')
                : formatPrice(price.amount, price.currency),
        muted: false,
    };
}

type EventRace = Event['races'][number];

/** The races grouped by the day they start on, the days and the races in each in order. */
const raceDays = computed(() => {
    // Map is the icon from lucide here, hence globalThis.
    const days = new globalThis.Map<string, EventRace[]>();

    for (const race of props.event.races) {
        days.set(race.date, [...(days.get(race.date) ?? []), race]);
    }

    return [...days.entries()]
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, races]) => ({
            date,
            races: [...races].sort((a, b) =>
                (a.time ?? '99:99').localeCompare(b.time ?? '99:99'),
            ),
        }));
});

const selectedRaceId = ref<number | null>(
    (props.event.races.find((race) => race.is_main) ?? props.event.races[0])
        ?.id ?? null,
);
const selectedRace = computed(
    () =>
        props.event.races.find((race) => race.id === selectedRaceId.value) ??
        props.event.races[0],
);

type RaceFact = {
    key: string;
    label: string;
    value: string;
    datetime?: string;
};

/** The facts of a race the card lists; the ones without a value are left out. */
function raceFacts(race: EventRace): RaceFact[] {
    const facts: RaceFact[] = [
        {
            key: 'start',
            label: t('events.races.start'),
            value: [raceStart(race), race.start_location]
                .filter(Boolean)
                .join(' · '),
            datetime: race.date,
        },
    ];

    if (race.finish_location) {
        facts.push({
            key: 'finish',
            label: t('events.races.finish'),
            value: race.finish_location,
        });
    }

    if (race.time_limit_minutes) {
        facts.push({
            key: 'time-limit',
            label: t('events.races.time_limit'),
            value: formatDuration(race.time_limit_minutes),
        });
    }

    if (race.min_age) {
        facts.push({
            key: 'min-age',
            label: t('events.races.min_age'),
            value: t('events.races.min_age_born', {
                age: race.min_age,
                year: Number(race.date.slice(0, 4)) - race.min_age,
            }),
        });
    }

    facts.push({
        key: 'medical',
        label: t('events.races.medical_certificate'),
        value: race.medical_certificate_required
            ? t('events.races.medical_required')
            : t('events.races.medical_not_required'),
    });

    if (race.aid_stations !== null) {
        facts.push({
            key: 'aid-stations',
            label: t('events.races.aid_stations'),
            value: formatNumber(race.aid_stations),
        });
    }

    if (race.elevation_gain_m) {
        facts.push({
            key: 'elevation',
            label: t('events.races.elevation'),
            value: `+${formatNumber(race.elevation_gain_m)} m`,
        });
    }

    if (race.participant_limit) {
        facts.push({
            key: 'participant-limit',
            label: t('events.races.participant_limit'),
            value: formatNumber(race.participant_limit),
        });
    }

    return facts;
}

const hasContact = computed(
    () => props.event.contact.email || props.event.contact.phone,
);
</script>

<template>
    <Head :title="seo.title ?? event.name" />

    <DefaultShell>
        <main
            class="mx-auto w-full max-w-6xl px-4 pt-4 sm:px-6 sm:pt-6"
            data-testid="event-page"
        >
            <Link
                :href="localeRoute(eventsIndex())"
                class="inline-flex items-center gap-1 rounded-full py-1.5 pr-2 text-sm font-bold text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                data-testid="event-page__back"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ t('events.back') }}
            </Link>

            <header data-testid="event-page__header">
                <div
                    class="relative mt-4 h-56 overflow-hidden rounded-lg border bg-secondary sm:h-80"
                    data-testid="event-page__hero"
                >
                    <img
                        v-if="event.image"
                        :src="event.image"
                        :alt="event.name"
                        class="absolute inset-0 h-full w-full object-cover"
                        data-testid="event-page__image"
                    />
                    <ContourArt
                        v-else
                        :seed="event.slug"
                        :width="1000"
                        :height="320"
                    />
                    <div
                        class="absolute inset-x-4 top-4 flex items-start justify-between gap-4"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                class="border-transparent bg-foreground font-bold text-background"
                                data-testid="event-page__category"
                                >{{ categoryLabel(event.category) }}</Badge
                            >
                            <Badge
                                v-if="event.races.length > 1"
                                variant="secondary"
                                class="border-border"
                                data-testid="event-page__race-count"
                                >{{
                                    t('events.races.count', {
                                        count: event.races.length,
                                    })
                                }}</Badge
                            >
                        </div>
                        <FavoriteButton
                            :event-slug="event.slug"
                            :event-name="event.name"
                            :favorited="isFavorite"
                            class="shrink-0 text-foreground"
                            data-testid="event-page__favorite"
                        />
                    </div>
                </div>

                <h1
                    class="mt-6 max-w-3xl text-3xl leading-[1.08] font-extrabold tracking-tight text-balance sm:text-5xl"
                    data-testid="event-page__title"
                >
                    {{ event.name }}
                </h1>

                <ul
                    class="mt-6 flex flex-wrap gap-4"
                    data-testid="event-page__summary"
                >
                    <li
                        v-for="fact in facts"
                        :key="fact.key"
                        class="flex min-w-0 items-center gap-4 rounded-lg border bg-card py-4 pr-4 pl-4 max-sm:w-full"
                        data-testid="event-page__summary-item"
                    >
                        <span
                            class="grid size-10 shrink-0 place-items-center rounded-lg bg-secondary text-primary"
                        >
                            <component
                                :is="fact.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </span>
                        <span class="min-w-0">
                            <span
                                class="block text-[11px] leading-tight font-bold tracking-widest text-muted-foreground uppercase"
                                data-testid="event-page__summary-label"
                                >{{ fact.label }}</span
                            >
                            <span
                                class="block font-bold"
                                data-testid="event-page__summary-value"
                            >
                                <time
                                    v-if="fact.datetime"
                                    :datetime="fact.datetime"
                                    >{{ fact.value }}</time
                                >
                                <template v-else>{{ fact.value }}</template>
                            </span>
                        </span>
                    </li>
                </ul>
            </header>

            <div
                class="grid gap-8 pt-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-12"
            >
                <div
                    class="flex min-w-0 flex-col gap-8"
                    data-testid="event-page__content"
                >
                    <!-- First in the content below the desktop layout, first in the sidebar on it. -->
                    <EventCountdown
                        v-if="countdown"
                        :countdown="countdown"
                        class="lg:hidden"
                    />
                    <div
                        v-if="banner"
                        role="alert"
                        class="flex items-start gap-4 rounded-lg border p-4 text-sm"
                        :class="
                            event.status === 'scheduled'
                                ? 'border-transparent bg-warn text-warn-foreground'
                                : 'border-destructive/50 bg-destructive/10 text-destructive'
                        "
                        data-testid="event-page__banner"
                    >
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <p>{{ t(`events.status.${banner}`) }}</p>
                    </div>
                    <section data-testid="event-page__about">
                        <h2 class="mb-2 text-xl font-extrabold tracking-tight">
                            {{ t('events.about') }}
                        </h2>
                        <p
                            v-if="event.description"
                            class="leading-relaxed whitespace-pre-line text-muted-foreground"
                            data-testid="event-page__description"
                        >
                            {{ event.description }}
                        </p>
                        <p
                            v-else
                            class="text-muted-foreground"
                            data-testid="event-page__no-description"
                        >
                            {{ t('events.no_description') }}
                        </p>
                    </section>
                    <section
                        v-if="event.races.length > 0"
                        data-testid="event-page__races"
                    >
                        <h2 class="text-xl font-extrabold tracking-tight">
                            {{ t('events.races.title') }}
                        </h2>
                        <template v-if="event.races.length > 1">
                            <p
                                class="mt-2 text-sm text-muted-foreground"
                                data-testid="event-page__schedule-hint"
                            >
                                {{
                                    t('events.races.schedule_hint', {
                                        races: t('events.races.count', {
                                            count: event.races.length,
                                        }),
                                        days: t(
                                            'events.races.days',
                                            raceDays.length,
                                        ),
                                    })
                                }}
                            </p>
                            <div
                                class="mt-4 flex flex-wrap gap-4"
                                data-testid="event-page__schedule"
                            >
                                <div
                                    v-for="day in raceDays"
                                    :key="day.date"
                                    class="flex min-w-[18rem] flex-1 flex-col gap-3 rounded-lg border bg-card p-4"
                                    data-testid="event-page__schedule-day"
                                >
                                    <h3
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        <time :datetime="day.date">{{
                                            formatDate(day.date, {
                                                weekday: 'long',
                                                day: 'numeric',
                                                month: 'long',
                                            })
                                        }}</time>
                                    </h3>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="race in day.races"
                                            :key="race.id"
                                            type="button"
                                            class="inline-flex min-h-11 items-center gap-2 rounded-full border px-4 py-2.5 text-left text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            :class="
                                                race.id === selectedRace?.id
                                                    ? 'border-transparent bg-primary text-primary-foreground'
                                                    : 'bg-card hover:border-primary'
                                            "
                                            :aria-pressed="
                                                race.id === selectedRace?.id
                                            "
                                            :aria-controls="`race-${race.id}`"
                                            data-testid="event-page__schedule-race"
                                            @click="selectedRaceId = race.id"
                                        >
                                            <span class="font-bold">{{
                                                race.name
                                            }}</span>
                                            <span
                                                :class="
                                                    race.id === selectedRace?.id
                                                        ? 'text-primary-foreground/80'
                                                        : 'text-muted-foreground'
                                                "
                                                >{{
                                                    race.time ??
                                                    t('events.races.time_tba')
                                                }}</span
                                            >
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <ul
                            class="mt-4 flex flex-col gap-4"
                            data-testid="event-page__race-list"
                        >
                            <li
                                v-for="race in selectedRace
                                    ? [selectedRace]
                                    : []"
                                :id="`race-${race.id}`"
                                :key="race.id"
                                class="rounded-lg border bg-card p-6"
                                data-testid="event-page__race"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <h3
                                        class="font-bold"
                                        data-testid="event-page__race-name"
                                    >
                                        {{ race.name }}
                                    </h3>
                                    <div class="flex flex-wrap gap-1.5">
                                        <Badge
                                            v-if="race.is_main"
                                            data-testid="event-page__race-main"
                                            >{{ t('events.races.main') }}</Badge
                                        >
                                        <Badge
                                            variant="secondary"
                                            data-testid="event-page__race-distance"
                                            >{{
                                                formatDistance(
                                                    race.distance_meters,
                                                )
                                            }}</Badge
                                        >
                                        <Badge
                                            v-if="race.discipline !== 'run'"
                                            variant="outline"
                                            data-testid="event-page__race-discipline"
                                            >{{
                                                t(
                                                    `events.races.discipline.${race.discipline}`,
                                                )
                                            }}</Badge
                                        >
                                        <Badge
                                            v-if="race.terrain"
                                            variant="outline"
                                            data-testid="event-page__race-terrain"
                                            >{{
                                                t(
                                                    `events.races.terrain.${race.terrain}`,
                                                )
                                            }}</Badge
                                        >
                                        <Badge
                                            v-if="race.certification"
                                            variant="outline"
                                            class="gap-1"
                                            data-testid="event-page__race-certification"
                                        >
                                            <BadgeCheck aria-hidden="true" />
                                            {{
                                                t(
                                                    `events.races.certifications.${race.certification}`,
                                                )
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="race.status === 'cancelled'"
                                            variant="destructive"
                                            data-testid="event-page__race-cancelled"
                                            >{{
                                                t('events.races.cancelled')
                                            }}</Badge
                                        >
                                        <RegistrationBadge
                                            v-if="race.status !== 'cancelled'"
                                            :state="race.registration_state"
                                            :price-rises-after="
                                                race.price_rises_after
                                            "
                                            data-testid="event-page__race-registration"
                                        />
                                    </div>
                                </div>

                                <dl
                                    class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                                    data-testid="event-page__race-details"
                                >
                                    <div
                                        v-for="fact in raceFacts(race)"
                                        :key="fact.key"
                                        class="flex flex-col gap-0.5"
                                        :data-testid="`event-page__race-${fact.key}`"
                                    >
                                        <dt
                                            class="text-[13px] text-muted-foreground"
                                        >
                                            {{ fact.label }}
                                        </dt>
                                        <dd class="font-medium">
                                            <time
                                                v-if="fact.datetime"
                                                :datetime="fact.datetime"
                                                >{{ fact.value }}</time
                                            >
                                            <template v-else>{{
                                                fact.value
                                            }}</template>
                                        </dd>
                                    </div>
                                </dl>

                                <section
                                    v-if="race.prices.length > 0"
                                    class="mt-6 flex flex-col gap-2"
                                    data-testid="event-page__race-prices"
                                >
                                    <h4 class="text-sm font-bold">
                                        {{ t('events.races.prices') }}
                                    </h4>
                                    <ul class="flex flex-col gap-1">
                                        <li
                                            v-for="(
                                                price, index
                                            ) in race.prices"
                                            :key="index"
                                            class="flex items-center gap-3 rounded-lg px-3 py-2"
                                            :class="
                                                price.state === 'current'
                                                    ? 'bg-secondary'
                                                    : ''
                                            "
                                            :data-state="price.state"
                                            data-testid="event-page__race-price"
                                        >
                                            <span
                                                class="flex min-w-0 flex-1 flex-col text-sm"
                                                :class="
                                                    price.state === 'past'
                                                        ? 'text-muted-foreground'
                                                        : ''
                                                "
                                            >
                                                <span class="font-medium">{{
                                                    price.label ||
                                                    t('events.races.entry')
                                                }}</span>
                                                <span
                                                    v-if="priceRange(price)"
                                                    class="text-[13px] text-muted-foreground"
                                                    >{{
                                                        priceRange(price)
                                                    }}</span
                                                >
                                            </span>
                                            <Badge
                                                v-if="price.state === 'current'"
                                                class="shrink-0 font-bold"
                                                >{{
                                                    t('events.races.current')
                                                }}</Badge
                                            >
                                            <span
                                                class="shrink-0 text-sm font-bold"
                                                :class="
                                                    price.state === 'past'
                                                        ? 'text-muted-foreground line-through'
                                                        : ''
                                                "
                                                >{{
                                                    price.amount === 0
                                                        ? t('events.races.free')
                                                        : formatPrice(
                                                              price.amount,
                                                              price.currency,
                                                          )
                                                }}</span
                                            >
                                        </li>
                                    </ul>
                                </section>

                                <RaceCutoffs
                                    v-if="race.cutoffs.length > 0"
                                    :cutoffs="race.cutoffs"
                                    :race-date="race.date"
                                />

                                <div
                                    v-if="
                                        race.registration_url || race.route_url
                                    "
                                    class="mt-6 flex flex-wrap gap-2"
                                >
                                    <Button
                                        v-if="race.registration_url"
                                        as-child
                                        variant="outline"
                                        class="max-sm:w-full"
                                    >
                                        <a
                                            data-testid="event-page__race-register"
                                            :href="race.registration_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Ticket aria-hidden="true" />
                                            {{ t('events.races.register') }}
                                        </a>
                                    </Button>
                                    <Button
                                        v-if="race.route_url"
                                        as-child
                                        variant="outline"
                                        class="max-sm:w-full"
                                    >
                                        <a
                                            data-testid="event-page__race-route"
                                            :href="race.route_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Route aria-hidden="true" />
                                            {{ t('events.course.route') }}
                                        </a>
                                    </Button>
                                </div>
                            </li>
                        </ul>
                    </section>
                    <RaceCourse
                        v-if="courseRaces.length > 0"
                        :races="courseRaces"
                    />
                    <PracticalInfo
                        v-if="event.sections.length > 0"
                        :sections="event.sections"
                    />
                    <RaceNews
                        v-if="news.posts.length > 0"
                        :posts="news.posts"
                        :total="news.total"
                        :event-slug="event.slug"
                    />
                    <RaceResults
                        v-if="editions.length > 1 || resultLinks.length > 0"
                        :editions="editions"
                        :links="resultLinks"
                    />
                    <section data-testid="event-page__facts">
                        <h2 class="mb-4 text-xl font-extrabold tracking-tight">
                            {{ t('events.facts.title') }}
                        </h2>
                        <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                            <div
                                v-if="event.organizer"
                                data-testid="event-page__organizer"
                            >
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Building2
                                        class="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ t('events.facts.organizer') }}
                                </dt>
                                <dd class="pl-6">
                                    <a
                                        v-if="event.organizer.website"
                                        :href="event.organizer.website"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="underline-offset-4 hover:underline"
                                        >{{ event.organizer.name }}</a
                                    >
                                    <template v-else>{{
                                        event.organizer.name
                                    }}</template>
                                </dd>
                            </div>
                            <div
                                v-if="event.venue"
                                data-testid="event-page__venue"
                            >
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <MapPin
                                        class="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ t('events.facts.venue') }}
                                </dt>
                                <dd class="pl-6">{{ event.venue }}</dd>
                            </div>
                            <div
                                v-if="event.region"
                                data-testid="event-page__region"
                            >
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Flag
                                        class="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ t('events.facts.region') }}
                                </dt>
                                <dd class="pl-6">{{ event.region.name }}</dd>
                            </div>
                            <div
                                v-if="event.series"
                                data-testid="event-page__series"
                            >
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Trophy
                                        class="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ t('events.facts.series') }}
                                </dt>
                                <dd class="pl-6">
                                    {{ event.series.name }}
                                    <template v-if="event.edition_number">
                                        &middot;
                                        {{
                                            t('events.facts.edition', {
                                                number: event.edition_number,
                                            })
                                        }}
                                    </template>
                                </dd>
                            </div>
                            <div data-testid="event-page__registration">
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Ticket
                                        class="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ t('events.facts.registration') }}
                                </dt>
                                <dd class="pl-6">
                                    {{
                                        t(
                                            `events.registration_status.${event.registration.status}`,
                                        )
                                    }}
                                    <span
                                        v-if="event.registration.opens_at"
                                        class="block text-muted-foreground"
                                        >{{
                                            t(
                                                'events.facts.registration_opens',
                                                {
                                                    date: registrationDate(
                                                        event.registration
                                                            .opens_at,
                                                    ),
                                                },
                                            )
                                        }}</span
                                    >
                                    <span
                                        v-if="event.registration.closes_at"
                                        class="block text-muted-foreground"
                                        >{{
                                            t(
                                                'events.facts.registration_closes',
                                                {
                                                    date: registrationDate(
                                                        event.registration
                                                            .closes_at,
                                                    ),
                                                },
                                            )
                                        }}</span
                                    >
                                </dd>
                            </div>
                            <div
                                v-if="hasContact"
                                data-testid="event-page__contact"
                            >
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Mail
                                        class="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ t('events.facts.contact') }}
                                </dt>
                                <dd class="pl-6 break-words">
                                    <a
                                        v-if="event.contact.email"
                                        :href="`mailto:${event.contact.email}`"
                                        class="block underline-offset-4 hover:underline"
                                        >{{ event.contact.email }}</a
                                    >
                                    <a
                                        v-if="event.contact.phone"
                                        :href="`tel:${event.contact.phone.replace(/\s+/g, '')}`"
                                        class="flex items-center gap-1 underline-offset-4 hover:underline"
                                    >
                                        <Phone
                                            class="size-3"
                                            aria-hidden="true"
                                        />{{ event.contact.phone }}</a
                                    >
                                </dd>
                            </div>
                        </dl>

                        <ul
                            v-if="factLinks.length > 0"
                            class="mt-4 flex flex-wrap gap-2"
                            data-testid="event-page__links"
                            :aria-label="t('events.facts.links')"
                        >
                            <li
                                v-for="link in factLinks"
                                :key="link.url"
                                data-testid="event-page__link"
                            >
                                <Button as-child variant="outline" size="sm">
                                    <a
                                        :href="link.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <component
                                            :is="linkIcons[link.type]"
                                            aria-hidden="true"
                                        />
                                        {{
                                            link.label ||
                                            t(`events.link_types.${link.type}`)
                                        }}
                                    </a>
                                </Button>
                            </li>
                        </ul>
                    </section>
                </div>

                <aside
                    class="flex flex-col gap-4 lg:sticky lg:top-24 lg:self-start"
                    data-testid="event-page__sidebar"
                >
                    <EventCountdown
                        v-if="countdown"
                        :countdown="countdown"
                        class="max-lg:hidden"
                    />
                    <div
                        class="flex flex-col gap-4 rounded-lg border bg-card p-6"
                    >
                        <h2 class="text-lg font-extrabold tracking-tight">
                            {{ t('events.sidebar.title') }}
                        </h2>
                        <ul
                            v-if="event.races.length > 0"
                            class="flex flex-col divide-y text-sm"
                            data-testid="event-page__sidebar-races"
                        >
                            <li
                                v-for="race in event.races"
                                :key="race.id"
                                class="flex items-baseline justify-between gap-4 py-2 first:pt-0 last:pb-0"
                                data-testid="event-page__sidebar-race"
                            >
                                <span class="min-w-0">
                                    <span class="block font-bold">{{
                                        race.name
                                    }}</span>
                                    <span class="text-muted-foreground">{{
                                        formatDistance(race.distance_meters)
                                    }}</span>
                                </span>
                                <span
                                    v-if="sidebarPrice(race)"
                                    class="shrink-0 font-bold"
                                    :class="
                                        sidebarPrice(race)?.muted
                                            ? 'text-muted-foreground'
                                            : ''
                                    "
                                    data-testid="event-page__sidebar-price"
                                    >{{ sidebarPrice(race)?.text }}</span
                                >
                            </li>
                        </ul>
                        <Button
                            v-if="event.registration_url"
                            as-child
                            size="lg"
                            class="w-full"
                        >
                            <a
                                :href="event.registration_url"
                                data-testid="event-page__register"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ t('events.register') }}
                                <ExternalLink aria-hidden="true" />
                            </a>
                        </Button>
                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                            data-testid="event-page__registration-unavailable"
                        >
                            {{
                                t(
                                    `events.registration_status.${event.registration.status}`,
                                )
                            }}
                        </p>
                        <Button
                            v-if="map"
                            as-child
                            variant="outline"
                            size="lg"
                            class="w-full"
                        >
                            <a
                                :href="map"
                                data-testid="event-page__map"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Map aria-hidden="true" />
                                {{ t('events.view_map') }}
                            </a>
                        </Button>
                    </div>
                    <KeyDates
                        v-if="event.key_dates.length > 0"
                        :dates="event.key_dates"
                    />
                </aside>
            </div>
            <div
                v-if="similarEvents.length > 0 || organizerEvents.length > 0"
                class="flex flex-col gap-12 pt-12 lg:pt-16"
                data-testid="event-page__more-races"
            >
                <MoreRaces
                    v-if="similarEvents.length > 0"
                    :title="t('events.related.similar')"
                    :events="similarEvents"
                    :favorites="favorites"
                />
                <MoreRaces
                    v-if="organizerEvents.length > 0 && event.organizer"
                    :title="
                        t('events.related.organizer', {
                            name: event.organizer.name,
                        })
                    "
                    :events="organizerEvents"
                    :favorites="favorites"
                />
            </div>
        </main>
    </DefaultShell>
</template>
