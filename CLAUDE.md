# CLAUDE.md — pms-connector

**Read first:** `CLAUDE.md`, `decisions.md` and `open-questions.md` in **vf-platform-dev/company**. That is the master brief for all VenueLink repos. If anything here conflicts with it, the company repo wins. Say so before acting.

## This repo
The API client for Macdonald's Roomez property management system: bookings, transfers and activities for Batonka and Pioneers first. Document every endpoint in `endpoints.md` (URL, fields, example response).

**Owner:** Macdonald. The owner approves pull requests here.

## Rules for this repo
- Map Roomez data into a vendor-neutral VenueLink model, so a second PMS can be added later without a rewrite.
- Roomez existed before VenueLink. Who owns what is an open question (open-questions #8). Don't copy Roomez internals into this repo until it's settled.
- No live credentials or real guest data in tests. Use fixtures.
- Never commit secrets, API keys, passwords, guest personal data or confidential lodge material. Use environment variables and keep `.env` files out of Git.
- Work on a branch named `short-description` and open a pull request. Don't push straight to `main`.
- If your change records or changes a decision, update `decisions.md` in the company repo too, or say that it needs updating.
