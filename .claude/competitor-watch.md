# Competitor watchlist

Maintained by the `competitors` agent. Update the state after every run.

Run log: 2026-10-28 first run (deep pass on Racehub, TrainerDay, GoTrail, HDsports; Greek discovery searches).

## Direct

| Competitor | URL | Last checked | State |
|---|---|---|---|
| Racehub | https://racehub.gr | 2026-10-28 | Closest rival. React-style SPA, EN/EL toggle, map-first home with split list/map, filters: search, distance, dates, type, elevation, ITRA, "More". Event page: type tag, distances, date, location, organiser, website, Register button, per-race start times, Add to calendar, Save Event, Share, historical weather for the race date (temp, wind, humidity, sweat loss). Home showed "8 events found" (off-season). Sitemap: 66 race URLs, newest lastmod 2026-07-09 (about 3.5 months stale); only 2 Crete races in sitemap (South Crete Night Run, Crete Half Marathon 2026). Still lists past races (Jul 2026). SEO weakness: raw HTML of event pages carries the home title/description (client-side rendering; title changes only after JS), no hreflang, JSON-LD only WebSite + Organization (no Event schema), no region/city landing pages. Organizers listed by contacting the author. Not verified: accounts, alerts. |
| TrainerDay Greece | https://www.trainerday.com/events/greece | 2026-10-28 | 366 events, 319 running (2026), 56 city pages, English only, OSM map, filter by sport/region/date. City counts overlap (radius based, so Litochoro 42 etc. are inflated). Crete cities: Rethymno 20, Agios Nikolaos 18, Heraklion 16, Chania 12 (overlapping, not unique events). Mixed quality (Athens Marathon appears several times as separate entries, Cyprus race in the Greek list). Editorial intro text per country. |
| GoTrail Greece | https://gotrail.run/trail-running-calendar/greece | 2026-10-28 | Trail focus but includes small road races. Greek and English URLs, region pages (Crete page: "5 races 2026-2027": Nov 15 Koiliaris Run, Dec 20 Keri Cross Country, Jan 16-17 Cretan Backyard Ultra, Feb 7 Retzakas Trail and Malles Nature Run). Filters: distance buckets, period, UTMB Index, ITRA; map; articles. Sister site running.life is a hiking calendar. |
| HDsports Greece | https://www.hdsports.org/running/greece-races-running-calendar | 2026-10-28 | German-owned editorial page, about 15 major races, table with 2026 date and 2027 date marked confirmed or unconfirmed, history text, affiliate book promo. Crete: only Crete Marathon (Chania). |
| Finishers | https://www.finishers.com/el/a/agonas-dromou/marathonios/marathonioi-sten-ellada | 2026-10-28 | NEW. French platform, 14 languages including Greek, 38 Greek races, themed landing pages (marathons, half marathons, by month, by country), organizer offers, favorites, "coming soon" 2027 dates with approximate wording ("early March 2027"), cookie banner. Crete coverage thin (Coast to Coast Ierapetra seen). |
| Ahotu | https://www.ahotu.com | 2026-10-28 | Global directory, tens of thousands of events, shallow Greece detail (unverified). |
| MyNextRun | https://www.mynextrun.com | 2026-10-28 | Global running calendar, about 15,000 events (unverified for Greece). |

## Indirect

| Competitor | URL | Note |
|---|---|---|
| Athens Authentic Marathon, Athens Half Marathon, Run Greece | https://www.wapp.gr/en/showcase/authentikos-marathonios | Own sites and registration by SEGAS, single login, myDATA invoices. |
| Crete Marathon | https://crete-marathon.gr | Organizer site, Chania. |
| Runner Magazine Greece | https://runnermagazine.gr | Editorial race guides, outdated lists. |
| SEGAS | https://www.segas.gr | Sanctions events, no runner calendar found. |
| RunSignup | https://runsignup.com | Registration platform, US. |
| athletin.gr | https://athletin.gr | Registration site used by Ierapetra organizers (seen as Racehub link target). Not examined. |
| ITRA race pages | https://itra.run | Hold Knossos Run records; trail/ultra only. |
| running.life | https://running.life | Hiking calendar, same family as GoTrail (guess from layout). |
| Facebook groups, Strava events, Google results | n/a | Likely main discovery channel for Greek runners (unverified). |

## Snapshot of NextRace for comparison (2026-10-28, develop.nextrace.gr)

10 Crete events Dec 2026 to Apr 2027, filters distance, region, month, city, map toggle, favorites, accounts (Google login, passkeys, 2FA), EN/EL, dark mode, SEO titles/descriptions in place (site closed to indexing).
