// The look of the moderation badge, as in the Figma "Admin status badge".
const base = 'rounded-full px-2.5 py-1 text-xs font-bold';

const states: Record<string, string> = {
    draft: 'bg-background text-muted-foreground',
    in_review: 'bg-amber-100 text-amber-900',
    published: 'bg-secondary text-primary',
};

export const stateClass = (state: string) => `${base} ${states[state] ?? ''}`;
