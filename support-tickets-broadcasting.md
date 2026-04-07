# Support Tickets Real-Time Broadcasting Plan

## Overview
The goal is to set up Laravel Reverb for real-time WebSocket broadcasting within the application, specifically focusing on introducing real-time updates for Support Tickets. Currently, the command `php artisan install:broadcasting` is suspended awaiting the installation of Node dependencies. We'll proceed with frontend configuration for Echo since standard installation missed `.ts` scaffolding because this is a standard JavaScript setup (`app.js`).

## Project Type
WEB

## Success Criteria
- Laravel Reverb is fully installed and operating locally.
- Authenticated users successfully connect to Reverb via Laravel Echo on the frontend.
- Creating a support ticket or replying to one triggers a real-time event.
- Connected users see their terminal UI/tables update immediately without page refresh.

## Tech Stack
- Backend: Laravel 11, Laravel Reverb (WebSockets).
- Frontend: Vue 3, Laravel Echo, Pusher-js (client library for Reverb).

## File Structure
- `config/broadcasting.php`: Configured for Reverb.
- `.env`: Holds Reverb credentials (REVERB_APP_ID, etc.).
- `resources/js/bootstrap.js`: Contains Echo and Pusher client initialization.
- `app/Events/TicketUpdated.php`: New broadcast event. 
- `resources/js/Pages/[SupportTicketComponent].vue`: Frontend pages updated to listen to Echo.

## Task Breakdown

| Task ID | Name | Agent | Skills | Priority | Dependencies | INPUT → OUTPUT → VERIFY |
|---------|------|-------|--------|----------|--------------|-------------------------|
| T1 | Finish Broadcasting Setup | `backend-specialist` | `bash-linux`, `nodejs-best-practices` | P0 | None | **INPUT**: User terminal prompt for npm packages.<br>**OUTPUT**: Echo and pusher-js installed in `package.json`, Reverb configured in `.env`.<br>**VERIFY**: Check `config/broadcasting.php` and `package.json` for pusher-js. |
| T2 | Configure Laravel Echo in Frontend | `frontend-specialist` | `frontend-design` | P1 | T1 | **INPUT**: `resources/js/bootstrap.js`.<br>**OUTPUT**: File imports `laravel-echo` and `pusher-js`, configures Echo instance for Reverb.<br>**VERIFY**: Compiles successfully with Vite (`npm run build`), no JS errors. |
| T3 | Create Ticket Events | `backend-specialist` | `php-patterns` | P2 | T1 | **INPUT**: `SupportTicketController`.<br>**OUTPUT**: New `TicketCreated` or `TicketUpdated` Event implementing `ShouldBroadcast`. Triggered on new tickets/replies.<br>**VERIFY**: Event is dispatched correctly when testing via `php artisan tinker`. |
| T4 | Update Support Ticket UI | `frontend-specialist` | `frontend-design`, `vue-patterns` | P3 | T2, T3 | **INPUT**: Support Ticket Vue pages.<br>**OUTPUT**: Components use `Echo.private('channel')` to listen for ticket updates and refresh status.<br>**VERIFY**: Real-time popups/updates works in UI. |

## ✅ Phase X: Verification COMPLETE
- Lint/Build: ✅ Success
- Security: ✅ Pass (No new dependency vulnerabilities)
- Date: 2026-04-08
