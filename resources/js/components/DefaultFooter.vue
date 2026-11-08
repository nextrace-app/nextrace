<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const year = new Date().getFullYear();
</script>

<template>
    <footer
        class="border-t bg-card text-sm text-muted-foreground"
        data-testid="site-footer"
    >
        <div class="px-4 py-4 sm:px-6 sm:py-6">
            <span data-testid="site-footer__copyright">{{
                t('footer.copyright', { year })
            }}</span>
        </div>
    </footer>
</template>
