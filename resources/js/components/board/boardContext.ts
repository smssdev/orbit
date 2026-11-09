import type { InjectionKey } from 'vue';
import type { Board } from '@/composables/useBoard';

export const boardKey: InjectionKey<Board> = Symbol('board');
