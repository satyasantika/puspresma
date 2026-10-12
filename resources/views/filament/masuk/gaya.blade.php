<style>
    /* Tema halaman masuk: kiri panel merek, kanan formulir. Palet sama dengan landing page (indigo + amber). */
    @media (min-width: 1024px) {
        .fi-simple-layout { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); min-height: 100dvh; align-items: stretch; justify-content: stretch; }
        .fi-simple-layout > .psp-merek { min-height: 100dvh; }
        .fi-simple-layout > .psp-merek { grid-column: 1; grid-row: 1 / span 4; }
        .fi-simple-layout > .fi-simple-main-ctn { grid-column: 2; grid-row: 1 / span 4; }
        .fi-simple-layout > .fi-simple-layout-header { position: absolute; inset-inline-end: 1rem; top: 1rem; z-index: 5; }
    }
    .fi-simple-layout { background: #f8fafc; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
    .dark .fi-simple-layout { background: #0b1020; }
    .fi-simple-main-ctn { align-items: center; padding: 2rem 1.25rem; }
    .fi-simple-main { box-shadow: none !important; background: transparent !important; padding-inline: 0; max-width: 26rem; width: 100%; }
    .fi-simple-main .fi-simple-header-heading { font-weight: 700; letter-spacing: -.01em; }
    .fi-simple-main .fi-btn-color-primary { background: #3730a3; }
    .fi-simple-main .fi-btn-color-primary:hover { background: #312e81; }

    .psp-merek { position: relative; overflow: hidden; color: #e0e7ff; background: linear-gradient(150deg, #1e1b4b 0%, #312e81 55%, #4338ca 100%); }
    .psp-merek::before { content: ""; position: absolute; width: 30rem; height: 30rem; right: -9rem; top: -8rem; border-radius: 50%; background: radial-gradient(circle, rgba(251,191,36,.28), transparent 65%); }
    .psp-merek::after { content: ""; position: absolute; width: 26rem; height: 26rem; left: -10rem; bottom: -10rem; border-radius: 50%; background: radial-gradient(circle, rgba(129,140,248,.35), transparent 65%); }
    .psp-merek-isi { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 2rem; height: 100%; padding: 2rem 1.5rem; max-width: 36rem; margin-inline: auto; }
    .psp-merek-logo { display: inline-flex; align-items: center; gap: .75rem; color: #fff; font-weight: 700; font-size: 1.25rem; text-decoration: none; }
    .psp-merek-logo small { display: block; font-weight: 500; font-size: .75rem; color: #c7d2fe; }
    .psp-merek-label { display: inline-block; margin: 0 0 1rem; padding: .25rem .75rem; border-radius: 999px; background: rgba(251,191,36,.15); color: #fcd34d; font-size: .75rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
    .psp-merek h2 { margin: 0; color: #fff; font-size: clamp(1.5rem, 2.6vw, 2.25rem); line-height: 1.2; font-weight: 700; letter-spacing: -.02em; }
    .psp-merek-lead { margin: 1rem 0 0; max-width: 30rem; color: #c7d2fe; line-height: 1.6; }
    .psp-merek-daftar { margin: 1.75rem 0 0; padding: 0; list-style: none; display: grid; gap: .75rem; font-size: .9rem; }
    .psp-merek-daftar li { display: flex; gap: .625rem; align-items: flex-start; }
    .psp-merek-daftar span { flex: none; display: inline-grid; place-items: center; width: 1.25rem; height: 1.25rem; border-radius: 50%; background: #fbbf24; color: #1e1b4b; font-size: .7rem; font-weight: 700; }
    .psp-merek-kaki { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; font-size: .8rem; }
    .psp-merek-kaki a { color: #c7d2fe; text-decoration: none; }
    .psp-merek-kaki a:hover, .psp-merek-kaki a:focus-visible { color: #fff; text-decoration: underline; }

    /* Layar sempit: panel merek menjadi pita ringkas di atas formulir. */
    @media (max-width: 1023.98px) {
        .psp-merek-isi { padding: 1rem 1.25rem; gap: 0; }
        .psp-merek-badan, .psp-merek-kaki { display: none; }
        .fi-simple-layout { display: flex; flex-direction: column; align-items: stretch; }
        .psp-merek { width: 100%; }
        .fi-simple-main-ctn { padding-top: 1.5rem; }
    }
</style>
