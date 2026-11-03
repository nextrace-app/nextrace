<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type Translated = { el: string; en: string };
export type Price = {
    label: Translated;
    amount_cents: number;
    valid_from: string;
    valid_until: string;
};
export type Race = {
    slug: string;
    name: Translated;
    is_main: boolean;
    discipline: string;
    terrain: string;
    distance_meters: number | string;
    elevation_gain_m: number | string;
    starts_at: string;
    status: string;
    registration_url: string;
    prices: Price[];
};
export type EventLink = { type: string; url: string; label: Translated };

defineProps<{
    errors: Record<string, string | undefined>;
    options: {
        disciplines: string[];
        terrains: string[];
        statuses: string[];
        linkTypes: string[];
    };
}>();

const races = defineModel<Race[]>('races', { required: true });
const links = defineModel<EventLink[]>('links', { required: true });

const { t } = useI18n();

const select = 'min-h-11 rounded-md border bg-transparent px-3 text-sm';

const blankRace = (): Race => ({
    slug: '',
    name: { el: '', en: '' },
    is_main: races.value.length === 0,
    discipline: 'run',
    terrain: 'road',
    distance_meters: '',
    elevation_gain_m: '',
    starts_at: '',
    status: 'scheduled',
    registration_url: '',
    prices: [],
});

const addRace = () => {
    races.value = [...races.value, blankRace()];
};

const removeRace = (index: number) => {
    const wasMain = races.value[index].is_main;

    races.value = races.value.filter((_, i) => i !== index);

    if (wasMain && races.value.length > 0) {
        races.value[0].is_main = true;
    }
};

const move = (index: number, by: -1 | 1) => {
    const target = index + by;

    if (target < 0 || target >= races.value.length) {
        return;
    }

    const next = [...races.value];

    [next[index], next[target]] = [next[target], next[index]];
    races.value = next;
};

const makeMain = (index: number) => {
    races.value.forEach((race, i) => (race.is_main = i === index));
};

const addPrice = (race: Race) => {
    race.prices.push({
        label: { el: '', en: '' },
        amount_cents: 0,
        valid_from: '',
        valid_until: '',
    });
};

const euros = (cents: number) => (cents / 100).toFixed(2);

const setAmount = (price: Price, value: string | number) => {
    price.amount_cents = Math.round(Number(value) * 100) || 0;
};

const addLink = () => {
    links.value = [
        ...links.value,
        { type: 'website', url: '', label: { el: '', en: '' } },
    ];
};

const removeLink = (index: number) => {
    links.value = links.value.filter((_, i) => i !== index);
};
</script>

<template>
    <section class="flex flex-col gap-6" data-testid="admin-races">
        <h2 class="text-xl font-bold">{{ t('admin.events.races.title') }}</h2>
        <InputError :message="errors.races" />

        <div
            v-if="races.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border bg-background px-6 py-8 text-center"
            data-testid="admin-races__empty"
        >
            <p class="font-medium">{{ t('admin.events.races.empty_title') }}</p>
            <p class="text-sm text-muted-foreground">
                {{ t('admin.events.races.empty_text') }}
            </p>
        </div>

        <div
            v-for="(race, index) in races"
            :key="index"
            class="flex flex-col gap-4 rounded-lg border bg-background p-4"
            data-testid="admin-races__race"
        >
            <div class="flex flex-wrap items-center gap-3">
                <p class="flex-1 font-medium">
                    {{ t('admin.events.races.race_n', { n: index + 1 }) }}
                    <span v-if="race.is_main" class="text-primary">
                        - {{ t('admin.events.races.main') }}
                    </span>
                </p>
                <button
                    v-if="!race.is_main"
                    type="button"
                    class="text-sm text-primary hover:underline"
                    @click="makeMain(index)"
                >
                    {{ t('admin.events.races.main') }}
                </button>
                <button
                    type="button"
                    class="text-sm text-primary hover:underline"
                    @click="move(index, -1)"
                >
                    {{ t('admin.events.races.move_up') }}
                </button>
                <button
                    type="button"
                    class="text-sm text-primary hover:underline"
                    @click="move(index, 1)"
                >
                    {{ t('admin.events.races.move_down') }}
                </button>
                <button
                    type="button"
                    class="text-sm text-destructive hover:underline"
                    data-testid="admin-races__remove"
                    @click="removeRace(index)"
                >
                    {{ t('admin.events.races.remove') }}
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.name') }} (EL)</Label>
                    <Input
                        v-model="race.name.el"
                        data-testid="admin-races__name-el"
                    />
                    <InputError :message="errors[`races.${index}.name.el`]" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.name') }} (EN)</Label>
                    <Input
                        v-model="race.name.en"
                        data-testid="admin-races__name-en"
                    />
                    <InputError :message="errors[`races.${index}.name.en`]" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.slug') }}</Label>
                    <Input
                        v-model="race.slug"
                        data-testid="admin-races__slug"
                    />
                    <InputError :message="errors[`races.${index}.slug`]" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.discipline') }}</Label>
                    <select v-model="race.discipline" :class="select">
                        <option
                            v-for="value in options.disciplines"
                            :key="value"
                            :value="value"
                        >
                            {{ t(`admin.events.races.disciplines.${value}`) }}
                        </option>
                    </select>
                    <InputError
                        :message="errors[`races.${index}.discipline`]"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.terrain') }}</Label>
                    <select v-model="race.terrain" :class="select">
                        <option
                            v-for="value in options.terrains"
                            :key="value"
                            :value="value"
                        >
                            {{ t(`admin.events.races.terrains.${value}`) }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.distance_meters') }}</Label>
                    <Input
                        v-model="race.distance_meters"
                        type="number"
                        min="1"
                        data-testid="admin-races__distance"
                    />
                    <InputError
                        :message="errors[`races.${index}.distance_meters`]"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>{{
                        t('admin.events.races.elevation_gain_m')
                    }}</Label>
                    <Input
                        v-model="race.elevation_gain_m"
                        type="number"
                        min="0"
                    />
                    <InputError
                        :message="errors[`races.${index}.elevation_gain_m`]"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.starts_at') }}</Label>
                    <Input
                        v-model="race.starts_at"
                        type="datetime-local"
                        data-testid="admin-races__starts-at"
                    />
                    <InputError :message="errors[`races.${index}.starts_at`]" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('admin.events.races.status') }}</Label>
                    <select v-model="race.status" :class="select">
                        <option
                            v-for="value in options.statuses"
                            :key="value"
                            :value="value"
                        >
                            {{ t(`admin.events.races.statuses.${value}`) }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label>{{
                        t('admin.events.races.registration_url')
                    }}</Label>
                    <Input v-model="race.registration_url" type="url" />
                    <InputError
                        :message="errors[`races.${index}.registration_url`]"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <p class="text-sm font-medium">
                    {{ t('admin.events.races.prices') }}
                </p>
                <div
                    v-for="(price, priceIndex) in race.prices"
                    :key="priceIndex"
                    class="grid gap-2 rounded-md border bg-card p-3 sm:grid-cols-5"
                    data-testid="admin-races__price"
                >
                    <Input
                        v-model="price.label.en"
                        :placeholder="`${t('admin.events.races.price_label')} (EN)`"
                    />
                    <Input
                        v-model="price.label.el"
                        :placeholder="`${t('admin.events.races.price_label')} (EL)`"
                    />
                    <div>
                        <Input
                            :model-value="euros(price.amount_cents)"
                            type="number"
                            min="0"
                            step="0.01"
                            :aria-label="t('admin.events.races.amount')"
                            data-testid="admin-races__amount"
                            @update:model-value="setAmount(price, $event)"
                        />
                        <InputError
                            :message="
                                errors[
                                    `races.${index}.prices.${priceIndex}.amount_cents`
                                ]
                            "
                        />
                    </div>
                    <Input
                        v-model="price.valid_from"
                        type="date"
                        :aria-label="t('admin.events.races.valid_from')"
                    />
                    <div class="flex gap-2">
                        <Input
                            v-model="price.valid_until"
                            type="date"
                            :aria-label="t('admin.events.races.valid_until')"
                        />
                        <button
                            type="button"
                            class="text-sm text-destructive hover:underline"
                            @click="race.prices.splice(priceIndex, 1)"
                        >
                            {{ t('admin.events.races.remove') }}
                        </button>
                    </div>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    class="self-start"
                    data-testid="admin-races__add-price"
                    @click="addPrice(race)"
                >
                    {{ t('admin.events.races.add_price') }}
                </Button>
            </div>
        </div>

        <Button
            type="button"
            variant="outline"
            class="self-start"
            data-testid="admin-races__add"
            @click="addRace"
        >
            {{ t('admin.events.races.add') }}
        </Button>

        <h2 class="text-xl font-bold">{{ t('admin.events.races.links') }}</h2>
        <div
            v-for="(link, index) in links"
            :key="index"
            class="grid gap-2 sm:grid-cols-4"
            data-testid="admin-races__link"
        >
            <select v-model="link.type" :class="select">
                <option
                    v-for="value in options.linkTypes"
                    :key="value"
                    :value="value"
                >
                    {{ t(`admin.events.races.link_types.${value}`) }}
                </option>
            </select>
            <div class="sm:col-span-2">
                <Input
                    v-model="link.url"
                    type="url"
                    :placeholder="t('admin.events.races.link_url')"
                    data-testid="admin-races__link-url"
                />
                <InputError :message="errors[`links.${index}.url`]" />
            </div>
            <div class="flex gap-2">
                <Input
                    v-model="link.label.en"
                    :placeholder="`${t('admin.events.races.link_label')} (EN)`"
                />
                <button
                    type="button"
                    class="text-sm text-destructive hover:underline"
                    @click="removeLink(index)"
                >
                    {{ t('admin.events.races.remove') }}
                </button>
            </div>
        </div>
        <Button
            type="button"
            variant="outline"
            class="self-start"
            data-testid="admin-races__add-link"
            @click="addLink"
        >
            {{ t('admin.events.races.add_link') }}
        </Button>
    </section>
</template>
