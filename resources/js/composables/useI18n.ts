import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update as updateLocale } from '@/routes/locale';

export type Direction = 'ltr' | 'rtl';

export type LocaleProps = {
    current: string;
    dir: Direction;
    available: { code: string; name: string }[];
};

type Replacements = Record<string, string | number>;

/**
 * Translate an English source string with the shared `translations` prop (lang/{locale}.json), the same way
 * Laravel's __() does: missing keys fall back to the English text, and `:name` placeholders are replaced.
 */
export function translate(
    translations: Record<string, string> | undefined,
    key: string,
    replacements: Replacements = {},
): string {
    let text = translations?.[key] ?? key;

    for (const [name, value] of Object.entries(replacements)) {
        text = text.replaceAll(`:${name}`, String(value));
    }

    return text;
}

export function useI18n() {
    const page = usePage();

    const locale = computed(() => page.props.locale as LocaleProps);
    const dir = computed<Direction>(() => locale.value?.dir ?? 'ltr');

    function t(key: string, replacements: Replacements = {}): string {
        return translate(
            page.props.translations as Record<string, string> | undefined,
            key,
            replacements,
        );
    }

    /** Pick the singular or plural form, like trans_choice() for the simple "one|many" case. */
    function tc(
        key: string,
        count: number,
        replacements: Replacements = {},
    ): string {
        const [one, many = one] = t(key).split('|');
        const form = count === 1 ? one : many;

        return translate({}, form, { count, ...replacements });
    }

    function setLocale(code: string): void {
        if (code === locale.value.current) {
            return;
        }

        router.put(
            updateLocale().url,
            { locale: code },
            { preserveScroll: true, preserveState: false },
        );
    }

    return { t, tc, locale, dir, setLocale };
}

/** Keep <html lang dir> in step with the page, so the browser lays the whole document out right to left. */
export function syncDocumentLocale(locale: LocaleProps | undefined): void {
    if (typeof document === 'undefined' || !locale) {
        return;
    }

    document.documentElement.lang = locale.current;
    document.documentElement.dir = locale.dir;
}
