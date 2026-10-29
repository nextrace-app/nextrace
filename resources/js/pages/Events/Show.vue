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
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEventFormat } from '@/composables/useEventFormat';
import { useLocale } from '@/composables/useLocale';
import { index as eventsIndex } from '@/routes/events';
import type { Event, LinkType } from '@/types';

const props = defineProps<{
    event: Event;
    isFavorite: boolean;
}>();

const { t } = useI18n();
const page = usePage();
const seo = computed(() => page.props.seo);
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
    props.event.ends_on
        ? formatDateRange(props.event.date, props.event.ends_on)
        : formatDate(props.event.date, {
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
    timing: Timer,
    other: Link2,
};

function raceStart(race: Event['races'][number]): string {
    const date = formatDate(race.date, {
        weekday: 'short',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    return race.time ? `${date}, ${formatTime(race.date, race.time)}` : date;
}

function priceRange(price: Event['races'][number]['prices'][number]): string {
    if (price.valid_until) {
        return t('events.races.until', { date: formatDate(price.valid_until) });
    }

    return price.valid_from
        ? t('events.races.from', { date: formatDate(price.valid_from) })
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
            datetime: props.event.date,
        },
    ];

    if (!props.event.ends_on) {
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

function lowestPrice(race: Event['races'][number]): string | null {
    if (race.prices.length === 0) {
        return null;
    }

    const cheapest = race.prices.reduce((least, price) =>
        price.amount < least.amount ? price : least,
    );

    return cheapest.amount === 0
        ? t('events.races.free')
        : formatPrice(cheapest.amount, cheapest.currency);
}

const courseRaces = computed(() =>
    props.event.races.filter(
        (race) => race.start_location || race.finish_location || race.route_url,
    ),
);

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
                    class="relative mt-3 h-56 overflow-hidden rounded-xl border bg-sand sm:h-80"
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
                        class="absolute inset-x-4 top-4 flex items-start justify-between gap-3"
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
                    class="mt-5 flex flex-wrap gap-3"
                    data-testid="event-page__summary"
                >
                    <li
                        v-for="fact in facts"
                        :key="fact.key"
                        class="flex min-w-0 items-center gap-3 rounded-xl border bg-card py-3 pr-4 pl-3 max-sm:w-full"
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
                    <div
                        v-if="banner"
                        role="alert"
                        class="flex items-start gap-3 rounded-lg border p-4 text-sm"
                        :class="
                            event.status === 'scheduled'
                                ? 'border-amber-500/50 bg-amber-500/10 text-amber-900 dark:text-amber-200'
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
                        <h2 class="mb-3 text-xl font-extrabold tracking-tight">
                            {{ t('events.races.title') }}
                        </h2>
                        <ul
                            class="flex flex-col gap-3"
                            data-testid="event-page__race-list"
                        >
                            <li
                                v-for="race in event.races"
                                :key="race.id"
                                class="rounded-xl border bg-card p-5"
                                data-testid="event-page__race"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <h3
                                        class="font-semibold"
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
                                    </div>
                                </div>

                                <dl
                                    class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2"
                                    data-testid="event-page__race-details"
                                >
                                    <div class="flex flex-col">
                                        <dt class="text-muted-foreground">
                                            {{ t('events.races.start') }}
                                        </dt>
                                        <dd>
                                            <time :datetime="race.date">{{
                                                raceStart(race)
                                            }}</time>
                                        </dd>
                                    </div>
                                    <div
                                        v-if="race.time_limit_minutes"
                                        class="flex flex-col"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{ t('events.races.time_limit') }}
                                        </dt>
                                        <dd>
                                            {{
                                                formatDuration(
                                                    race.time_limit_minutes,
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div
                                        v-if="race.min_age"
                                        class="flex flex-col"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{ t('events.races.min_age') }}
                                        </dt>
                                        <dd>
                                            {{
                                                t(
                                                    'events.races.min_age_value',
                                                    {
                                                        age: race.min_age,
                                                    },
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div
                                        v-if="race.participant_limit"
                                        class="flex flex-col"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{
                                                t(
                                                    'events.races.participant_limit',
                                                )
                                            }}
                                        </dt>
                                        <dd>
                                            {{
                                                formatNumber(
                                                    race.participant_limit,
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div
                                        v-if="race.elevation_gain_m"
                                        class="flex flex-col"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{ t('events.races.elevation') }}
                                        </dt>
                                        <dd>
                                            {{
                                                formatNumber(
                                                    race.elevation_gain_m,
                                                )
                                            }}
                                            m
                                        </dd>
                                    </div>
                                    <div
                                        v-if="race.prices.length > 0"
                                        data-testid="event-page__race-prices"
                                        class="flex flex-col sm:col-span-2"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{ t('events.races.prices') }}
                                        </dt>
                                        <dd>
                                            <ul
                                                class="mt-1 flex flex-wrap gap-2"
                                            >
                                                <li
                                                    v-for="(
                                                        price, index
                                                    ) in race.prices"
                                                    :key="index"
                                                    class="rounded-md bg-muted px-2.5 py-1"
                                                    data-testid="event-page__race-price"
                                                >
                                                    <span class="font-medium">{{
                                                        price.amount === 0
                                                            ? t(
                                                                  'events.races.free',
                                                              )
                                                            : formatPrice(
                                                                  price.amount,
                                                                  price.currency,
                                                              )
                                                    }}</span>
                                                    <span
                                                        v-if="
                                                            price.label ||
                                                            priceRange(price)
                                                        "
                                                        class="text-muted-foreground"
                                                    >
                                                        &middot;
                                                        {{
                                                            [
                                                                price.label,
                                                                priceRange(
                                                                    price,
                                                                ),
                                                            ]
                                                                .filter(Boolean)
                                                                .join(', ')
                                                        }}
                                                    </span>
                                                </li>
                                            </ul>
                                        </dd>
                                    </div>
                                </dl>

                                <Button
                                    v-if="race.registration_url"
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="mt-3 w-full sm:w-auto"
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
                            </li>
                        </ul>
                    </section>
                    <section
                        v-if="courseRaces.length > 0"
                        data-testid="event-page__course"
                    >
                        <h2 class="mb-3 text-xl font-extrabold tracking-tight">
                            {{ t('events.course.title') }}
                        </h2>
                        <ul class="flex flex-col gap-3">
                            <li
                                v-for="race in courseRaces"
                                :key="race.id"
                                class="rounded-xl border bg-card p-5"
                                data-testid="event-page__course-race"
                            >
                                <h3 class="font-semibold">{{ race.name }}</h3>
                                <dl
                                    class="mt-2 grid gap-2 text-sm sm:grid-cols-2"
                                >
                                    <div
                                        v-if="race.start_location"
                                        class="flex flex-col"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{ t('events.course.start') }}
                                        </dt>
                                        <dd>{{ race.start_location }}</dd>
                                    </div>
                                    <div
                                        v-if="race.finish_location"
                                        class="flex flex-col"
                                    >
                                        <dt class="text-muted-foreground">
                                            {{ t('events.course.finish') }}
                                        </dt>
                                        <dd>{{ race.finish_location }}</dd>
                                    </div>
                                </dl>
                                <a
                                    v-if="race.route_url"
                                    :href="race.route_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary underline-offset-4 hover:underline"
                                    data-testid="event-page__course-route"
                                >
                                    <Route class="size-4" aria-hidden="true" />
                                    {{ t('events.course.route') }}
                                </a>
                            </li>
                        </ul>
                    </section>
                    <section data-testid="event-page__facts">
                        <h2 class="mb-3 text-xl font-extrabold tracking-tight">
                            {{ t('events.facts.title') }}
                        </h2>
                        <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            <div
                                v-if="event.organizer"
                                class="flex gap-2"
                                data-testid="event-page__organizer"
                            >
                                <Building2
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-muted-foreground">
                                        {{ t('events.facts.organizer') }}
                                    </dt>
                                    <dd>
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
                            </div>
                            <div
                                v-if="event.venue"
                                class="flex gap-2"
                                data-testid="event-page__venue"
                            >
                                <MapPin
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-muted-foreground">
                                        {{ t('events.facts.venue') }}
                                    </dt>
                                    <dd>{{ event.venue }}</dd>
                                </div>
                            </div>
                            <div
                                v-if="event.region"
                                class="flex gap-2"
                                data-testid="event-page__region"
                            >
                                <Flag
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-muted-foreground">
                                        {{ t('events.facts.region') }}
                                    </dt>
                                    <dd>{{ event.region.name }}</dd>
                                </div>
                            </div>
                            <div
                                v-if="event.series"
                                class="flex gap-2"
                                data-testid="event-page__series"
                            >
                                <Trophy
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-muted-foreground">
                                        {{ t('events.facts.series') }}
                                    </dt>
                                    <dd>
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
                            </div>
                            <div
                                class="flex gap-2"
                                data-testid="event-page__registration"
                            >
                                <Ticket
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div>
                                    <dt class="text-muted-foreground">
                                        {{ t('events.facts.registration') }}
                                    </dt>
                                    <dd>
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
                            </div>
                            <div
                                v-if="hasContact"
                                class="flex gap-2"
                                data-testid="event-page__contact"
                            >
                                <Mail
                                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <div class="min-w-0">
                                    <dt class="text-muted-foreground">
                                        {{ t('events.facts.contact') }}
                                    </dt>
                                    <dd class="break-words">
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
                            </div>
                        </dl>

                        <ul
                            v-if="event.links.length > 0"
                            class="mt-4 flex flex-wrap gap-2"
                            data-testid="event-page__links"
                            :aria-label="t('events.facts.links')"
                        >
                            <li
                                v-for="link in event.links"
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
                    <div
                        class="flex flex-col gap-3 rounded-xl border bg-card p-5"
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
                                class="flex items-baseline justify-between gap-3 py-2 first:pt-0 last:pb-0"
                                data-testid="event-page__sidebar-race"
                            >
                                <span class="min-w-0">
                                    <span class="block font-semibold">{{
                                        race.name
                                    }}</span>
                                    <span class="text-muted-foreground">{{
                                        formatDistance(race.distance_meters)
                                    }}</span>
                                </span>
                                <span
                                    v-if="lowestPrice(race)"
                                    class="shrink-0 font-bold"
                                    data-testid="event-page__sidebar-price"
                                    >{{ lowestPrice(race) }}</span
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
                </aside>
            </div>
        </main>
    </DefaultShell>
</template>
