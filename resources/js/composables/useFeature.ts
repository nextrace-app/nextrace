import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';

/**
 * Whether the feature flag with that key (admin > Features) is on. Flags
 * only show or hide things; the server still guards what a flag protects.
 */
export function useFeature(key: string): ComputedRef<boolean> {
    const page = usePage();

    return computed(() => page.props.features.includes(key));
}
