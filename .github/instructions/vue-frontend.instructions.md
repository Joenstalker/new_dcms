---
description: Frontend Vue.js guidelines for DCMS - Use when working with Vue components, pages, or frontend code
applyTo: "**/*.vue"
---

# Frontend Vue.js Guidelines

## Framework
- Use Vue 3 with Composition API
- Integrate with Inertia.js for server-side routing and data passing
- Follow single-file component structure

## Conventions
- Use `<script setup>` for component logic
- Import Inertia helpers: `import { usePage, useForm } from '@inertiajs/vue3'`
- Handle tenant-specific data via props from Inertia
- Use reactive forms for user inputs

## Pitfalls
- Avoid direct API calls; use Inertia links and forms for navigation
- Ensure tenant context is passed correctly from backend