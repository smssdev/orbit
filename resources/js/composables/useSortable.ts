import Sortable from 'sortablejs';
import type { Ref } from 'vue';
import { onBeforeUnmount, onMounted, watch } from 'vue';

type Options = {
    /** Lists sharing a group exchange items. */
    group: string;
    /** Only these parts of an item start a drag (the whole item when omitted). */
    handle?: string;
    disabled: () => boolean;
    /** Called with the item's `data-id`, the target list's `data-list-id` and the item's new index there. */
    onMove: (itemId: number, listId: number, index: number) => void;
};

/**
 * Pointer drag and drop for a list rendered by Vue, using SortableJS.
 *
 * SortableJS moves the element itself. Vue has to stay the only one changing the DOM, so on drop the element
 * goes back where it was and `onMove` changes the state, which Vue renders. Items are the list's children with
 * `data-sortable-item`; ones with `data-pending` (not saved yet) stay put.
 */
export function useSortable(list: Ref<HTMLElement | null>, options: Options) {
    let sortable: Sortable | null = null;

    onMounted(() => {
        if (!list.value) {
            return;
        }

        sortable = Sortable.create(list.value, {
            group: options.group,
            handle: options.handle,
            draggable: '[data-sortable-item]',
            filter: '[data-pending], button, input, textarea, [role="menuitem"]',
            preventOnFilter: false,
            disabled: options.disabled(),
            animation: 150,
            // Pointer events instead of native drag and drop: the same look everywhere, and touch support.
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 4,
            delay: 150,
            delayOnTouchOnly: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: ({ item, from, to, oldIndex, newIndex }) => {
                if (oldIndex === undefined || newIndex === undefined) {
                    return;
                }

                putBack(item, from, oldIndex);

                if (from === to && oldIndex === newIndex) {
                    return;
                }

                options.onMove(
                    Number(item.dataset.id),
                    Number(to.dataset.listId),
                    newIndex,
                );
            },
        });
    });

    watch(options.disabled, (disabled) =>
        sortable?.option('disabled', disabled),
    );

    onBeforeUnmount(() => {
        sortable?.destroy();
        sortable = null;
    });
}

/**
 * Puts a dragged element back at its old index among the list's items, before the text node Vue uses to mark the
 * end of a list, so Vue finds the DOM as it rendered it.
 */
function putBack(item: HTMLElement, list: HTMLElement, index: number): void {
    item.remove();

    const items = list.querySelectorAll(':scope > [data-sortable-item]');
    const reference = items[index] ?? items[items.length - 1]?.nextSibling;

    list.insertBefore(item, reference ?? list.lastChild);
}
