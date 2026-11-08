<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { useEventFormat } from '@/composables/useEventFormat';
import type { RegistrationState } from '@/types';

/**
 * Where a registration stands: open, free, sold out, closed or not open yet,
 * or "price rises soon" for an open one whose price goes up within a week.
 * Nothing is shown when the state is unknown.
 */
const props = defineProps<{
    state: RegistrationState;
    /** The last day of the current price, for "Price rises after ...". */
    priceRisesAfter?: string | null;
    /** Say "Price rises soon" without a date, as the event card does. */
    priceRisesSoon?: boolean;
}>();

const { t } = useI18n();
const { formatDate } = useEventFormat();

const rising = computed(
    () =>
        props.state === 'open' &&
        (props.priceRisesSoon || Boolean(props.priceRisesAfter)),
);

const label = computed(() => {
    if (rising.value) {
        return props.priceRisesAfter
            ? t('events.registration_status.price_rises_after', {
                  date: formatDate(props.priceRisesAfter, {
                      day: 'numeric',
                      month: 'short',
                  }),
              })
            : t('events.registration_status.price_rises_soon');
    }

    return props.state === 'unknown'
        ? null
        : t(`events.registration_status.${props.state}`);
});

const tone = computed(() => {
    if (rising.value || props.state === 'sold_out') {
        return 'border-transparent bg-warn font-bold text-warn-foreground';
    }

    return props.state === 'open' || props.state === 'free'
        ? 'border-transparent bg-secondary font-bold text-secondary-foreground'
        : 'font-bold text-muted-foreground';
});
</script>

<template>
    <Badge
        v-if="label"
        variant="outline"
        :class="tone"
        :data-state="rising ? 'price_rises' : state"
        data-testid="registration-badge"
        >{{ label }}</Badge
    >
</template>
