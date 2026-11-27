<!DOCTYPE html>
<html lang="el">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Σύντομα κοντά σας - NextRace</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,800" rel="stylesheet">

        {{-- Self-contained on purpose: maintenance mode must not depend on the Vite build. --}}
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
                background: #f6f4ee;
                color: #14231f;
            }
            main {
                display: flex;
                min-height: 100vh;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 4rem 1rem;
                text-align: center;
            }
            .brand {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 3rem;
                font-size: 1.25rem;
                font-weight: 800;
                letter-spacing: -0.025em;
            }
            .brand svg { width: 2.5rem; height: 2.5rem; flex-shrink: 0; }
            h1 {
                margin: 0;
                font-size: 1.875rem;
                line-height: 2.25rem;
                font-weight: 800;
                letter-spacing: -0.025em;
            }
            p {
                max-width: 28rem;
                margin: 1rem 0 0;
                line-height: 1.5rem;
                color: #5d6b66;
            }
            @media (min-width: 640px) {
                h1 { font-size: 3rem; line-height: 1; }
            }
        </style>
    </head>
    <body>
        <main data-testid="error-page--maintenance">
            <div class="brand" data-testid="error-page__brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true">
                    <circle cx="16" cy="16" r="16" fill="#e3f2ea" />
                    <circle cx="16" cy="12" r="5" fill="#0f7a5c" />
                    <path
                        d="M5 20c3-2.5 5-2.5 8 0s5 2.5 8 0 4-2 6-.7M7 25c2.6-2 4.4-2 7 0s4.4 2 7 0 3-1.6 4.5-.6"
                        fill="none"
                        stroke="#14231f"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
                <span>Nextrace</span>
            </div>
            <h1 data-testid="error-page__title">Σύντομα κοντά σας</h1>
            <p data-testid="error-page__text">Το NextRace είναι ακόμη υπό κατασκευή. Το ημερολόγιο αγώνων της Ελλάδας έρχεται, ξαναδές σύντομα.</p>
        </main>
    </body>
</html>
