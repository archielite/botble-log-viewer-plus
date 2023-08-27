<template>
    <Listbox as="div" v-model="fileStore.selectedFileTypes" multiple>
        <ListboxLabel class="block ml-1 text-sm text-gray-500 dark:text-gray-400">Selected file types</ListboxLabel>

        <div class="relative mt-1">
            <ListboxButton
                id="hosts-toggle-button"
                class="relative w-full py-2 pl-4 pr-10 text-sm text-left text-gray-800 bg-white border border-gray-300 rounded-md cursor-default cursor-pointer dark:text-gray-200 dark:border-gray-700 dark:bg-gray-800 hover:border-brand-600 hover:dark:border-brand-800 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
                <span class="block truncate">{{ fileStore.selectedFileTypesString }}</span>
                <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                    <ChevronDownIcon class="w-5 h-5 text-gray-400" aria-hidden="true" />
                </span>
            </ListboxButton>

            <transition
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <ListboxOptions
                    class="absolute z-20 w-full py-1 mt-1 overflow-auto text-sm bg-white border border-gray-200 rounded-md shadow-md max-h-60 dark:bg-gray-800 dark:border-gray-700 ring-1 ring-brand ring-opacity-5 focus:outline-none"
                >
                    <ListboxOption
                        as="template"
                        v-for="fileType in fileStore.fileTypesAvailable"
                        :key="fileType.identifier"
                        :value="fileType.identifier"
                        v-slot="{ active, selected }"
                    >
                        <li
                            :class="[
                                active ? 'text-white bg-brand-600' : 'text-gray-900 dark:text-gray-300',
                                'relative cursor-default select-none py-2 pl-3 pr-9',
                            ]"
                        >
                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{
                                fileType.name
                            }}</span>

                            <span
                                v-if="selected"
                                :class="[
                                    active ? 'text-white' : 'text-brand-600',
                                    'absolute inset-y-0 right-0 flex items-center pr-4',
                                ]"
                            >
                                <CheckIcon class="w-5 h-5" aria-hidden="true" />
                            </span>
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>

<script setup>
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { CheckIcon, ChevronDownIcon } from '@heroicons/vue/20/solid'
import { useRouter } from 'vue-router'
import { useFileStore } from '../stores/files.js'
const router = useRouter()
const fileStore = useFileStore()
</script>
