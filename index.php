<?php
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ExamHub — Ujian Digital dalam Satu Tempat</title>

    <meta
        name="description"
        content="ExamHub adalah platform ujian digital untuk membantu guru mengelola ujian dan siswa mengikuti ujian secara terstruktur."
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >
    <link rel="stylesheet" href="assets/cbt-theme.css?v=20261024-2">

    <style>
        :root {
            --landing-brand: #a8464f;
            --landing-brand-dark: #84363e;
            --landing-brand-soft: #f7e4e3;
            --landing-yellow: #f2c879;
            --landing-yellow-soft: #fff3cf;
            --landing-paper: #fff9df;
            --landing-ink: #334442;
            --landing-ink-soft: #526866;
            --landing-muted: #748784;
            --landing-line: #e8dfbd;
            --landing-white: #ffffff;
        }

        body {
            overflow-x: hidden;
        }

        .landing-page {
            position: relative;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            overflow: hidden;
        }

        .landing-container {
            position: relative;
            width: min(1160px, calc(100% - 40px));
            margin-inline: auto;
        }

        /* ---------- Navigation ---------- */

        .landing-nav {
            position: relative;
            z-index: 10;
            padding: 20px 0;
        }

        .landing-nav-inner {
            display: flex;
            min-height: 58px;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 8px 10px 8px 14px;
            border: 1px solid rgba(232, 223, 189, .92);
            border-radius: 18px;
            background: rgba(255, 255, 255, .92);
            box-shadow:
                0 2px 4px rgba(132, 54, 62, .04),
                0 12px 30px rgba(132, 54, 62, .08);
            backdrop-filter: blur(14px);
        }

        .landing-brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: var(--landing-ink);
            text-decoration: none;
        }

        .landing-brand:hover {
            color: var(--landing-ink);
        }

        .landing-brand-icon {
            display: grid;
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            place-items: center;
            color: var(--landing-brand-dark);
            border: 1px solid #e0b85f;
            border-radius: 12px;
            background: var(--landing-yellow);
            font-size: 19px;
            transform: rotate(-3deg);
        }

        .landing-brand-name {
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .landing-brand-name span {
            color: var(--landing-brand);
        }

        .nav-action {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 17px;
            color: #fff;
            border: 1px solid var(--landing-brand);
            border-radius: 11px;
            background: var(--landing-brand);
            font-size: .86rem;
            font-weight: 700;
            text-decoration: none;
            transition:
                background-color 180ms ease,
                border-color 180ms ease,
                transform 180ms ease,
                box-shadow 180ms ease;
        }

        .nav-action:hover {
            color: #fff;
            border-color: var(--landing-brand-dark);
            background: var(--landing-brand-dark);
            box-shadow: 0 7px 16px rgba(132, 54, 62, .18);
            transform: translateY(-1px);
        }

        /* ---------- Hero ---------- */

        .landing-main {
            flex: 1;
            padding: 34px 0 62px;
        }

        .hero-shell {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            padding: clamp(36px, 6vw, 72px);
            border: 1px solid rgba(132, 54, 62, .18);
            border-radius: 30px;
            background: var(--landing-brand);
            box-shadow:
                0 12px 24px -10px rgba(132, 54, 62, .22),
                0 34px 70px -24px rgba(132, 54, 62, .28);
        }

        .hero-shell::before {
            content: '';
            position: absolute;
            z-index: -1;
            top: -105px;
            right: -80px;
            width: 300px;
            height: 300px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 50%;
            box-shadow:
                0 0 0 42px rgba(255, 255, 255, .035),
                0 0 0 84px rgba(255, 255, 255, .025);
        }

        .hero-shell::after {
            content: '';
            position: absolute;
            z-index: -1;
            right: 4%;
            bottom: -55px;
            width: 190px;
            height: 190px;
            opacity: .18;
            background-image:
                linear-gradient(rgba(255, 255, 255, .72) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .72) 1px, transparent 1px);
            background-size: 19px 19px;
            transform: rotate(7deg);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(340px, .95fr);
            align-items: center;
            gap: clamp(44px, 7vw, 86px);
        }

        .hero-copy {
            position: relative;
            z-index: 2;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            padding: 7px 12px;
            color: #fff9d5;
            border: 1px solid rgba(255, 243, 207, .35);
            border-radius: 999px;
            background: rgba(255, 243, 207, .10);
            font-size: .69rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .hero-title {
            max-width: 680px;
            margin: 0 0 22px;
            color: #fff;
            font-size: clamp(2.7rem, 5.8vw, 5rem);
            line-height: 1.02;
            letter-spacing: -.065em;
        }

        .hero-title .highlight {
            display: inline-block;
            color: var(--landing-yellow-soft);
        }

        .hero-description {
            max-width: 610px;
            margin: 0 0 30px;
            color: rgba(255, 255, 255, .78);
            font-size: clamp(.96rem, 1.8vw, 1.08rem);
            line-height: 1.8;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
        }

        .primary-action,
        .secondary-action {
            display: inline-flex;
            min-height: 52px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 21px;
            border-radius: 12px;
            font-size: .92rem;
            font-weight: 700;
            text-decoration: none;
            transition:
                color 180ms ease,
                background-color 180ms ease,
                border-color 180ms ease,
                transform 180ms ease,
                box-shadow 180ms ease;
        }

        .primary-action {
            color: var(--landing-brand-dark);
            border: 1px solid var(--landing-yellow);
            background: var(--landing-yellow);
            box-shadow: 0 9px 22px rgba(71, 40, 18, .14);
        }

        .primary-action:hover {
            color: var(--landing-brand-dark);
            border-color: #f7dfa4;
            background: #f7dfa4;
            box-shadow: 0 13px 26px rgba(71, 40, 18, .20);
            transform: translateY(-2px);
        }

        .secondary-action {
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .35);
            background: rgba(255, 255, 255, .08);
        }

        .secondary-action:hover {
            color: var(--landing-brand-dark);
            border-color: #fff;
            background: #fff;
            transform: translateY(-2px);
        }

        .trust-note {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: rgba(255, 255, 255, .65);
            font-size: .79rem;
        }

        .trust-note i {
            color: var(--landing-yellow-soft);
        }

        /* ---------- Exam preview ---------- */

        .preview-wrap {
            position: relative;
            z-index: 2;
            min-width: 0;
        }

        .preview-card {
            position: relative;
            max-width: 430px;
            margin-inline: auto;
            padding: 24px;
            color: var(--landing-ink);
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 24px 55px rgba(69, 24, 30, .24);
            transform: rotate(1deg);
        }

        .preview-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 21px;
        }

        .preview-label {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 10px;
            font-size: .84rem;
            font-weight: 700;
        }

        .preview-icon {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            place-items: center;
            color: var(--landing-brand);
            border: 1px solid #ebc9c8;
            border-radius: 11px;
            background: var(--landing-brand-soft);
            font-size: 17px;
        }

        .preview-status {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            color: #376a42;
            border: 1px solid #b9dbbe;
            border-radius: 8px;
            background: #edf8ef;
            font-size: .65rem;
            font-weight: 700;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4f8f58;
        }

        .preview-title {
            margin: 0 0 5px;
            color: var(--landing-ink);
            font-size: 1.14rem;
            letter-spacing: -.035em;
        }

        .preview-subtitle {
            margin: 0 0 18px;
            color: var(--landing-muted);
            font-size: .76rem;
        }

        .question-box {
            padding: 16px;
            border: 1px solid var(--landing-line);
            border-radius: 14px;
            background: #fffdf2;
        }

        .question-number {
            margin-bottom: 10px;
            color: var(--landing-brand);
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .question-text {
            margin: 0 0 13px;
            color: var(--landing-ink);
            font-size: .79rem;
            font-weight: 600;
            line-height: 1.55;
        }

        .answer-option {
            display: flex;
            min-height: 37px;
            align-items: center;
            gap: 9px;
            margin-top: 7px;
            padding: 8px 10px;
            color: var(--landing-ink-soft);
            border: 1px solid #ebe4ca;
            border-radius: 9px;
            background: #fff;
            font-size: .7rem;
        }

        .answer-letter {
            display: grid;
            width: 22px;
            height: 22px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid #ded4b1;
            border-radius: 6px;
            font-size: .62rem;
            font-weight: 700;
        }

        .answer-option.selected {
            color: var(--landing-brand-dark);
            border-color: #d99aa0;
            background: var(--landing-brand-soft);
            font-weight: 600;
        }

        .answer-option.selected .answer-letter {
            color: #fff;
            border-color: var(--landing-brand);
            background: var(--landing-brand);
        }

        .answer-option .check {
            margin-left: auto;
            color: var(--landing-brand);
        }

        .progress-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 16px 0 8px;
            color: var(--landing-muted);
            font-size: .66rem;
            font-weight: 600;
        }

        .progress-track {
            overflow: hidden;
            height: 6px;
            border-radius: 999px;
            background: #eee7cf;
        }

        .progress-fill {
            width: 65%;
            height: 100%;
            border-radius: inherit;
            background: var(--landing-brand);
        }

        .floating-note {
            position: absolute;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 13px;
            color: var(--landing-ink);
            border: 1px solid var(--landing-line);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(69, 24, 30, .15);
            animation: float 4s ease-in-out infinite;
        }

        .floating-note.top {
            top: 11%;
            left: -9%;
        }

        .floating-note.bottom {
            right: -7%;
            bottom: 10%;
            animation-delay: -2s;
        }

        .floating-note-icon {
            display: grid;
            width: 32px;
            height: 32px;
            flex: 0 0 auto;
            place-items: center;
            color: var(--landing-brand);
            border-radius: 9px;
            background: var(--landing-brand-soft);
        }

        .floating-note.bottom .floating-note-icon {
            color: #8a641d;
            background: var(--landing-yellow-soft);
        }

        .floating-note strong,
        .floating-note span {
            display: block;
        }

        .floating-note strong {
            margin-bottom: 2px;
            font-size: .68rem;
        }

        .floating-note span {
            color: var(--landing-muted);
            font-size: .57rem;
        }

        /* ---------- Benefits ---------- */

        .benefits-section {
            padding: 68px 0 20px;
        }

        .section-heading {
            max-width: 680px;
            margin: 0 auto 32px;
            text-align: center;
        }

        .section-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 11px;
            color: var(--landing-brand);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .section-title {
            margin: 0 0 11px;
            color: var(--landing-ink);
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            line-height: 1.15;
        }

        .section-description {
            margin: 0;
            color: var(--landing-muted);
            font-size: .94rem;
            line-height: 1.75;
        }

        .benefit-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .benefit-card {
            padding: 25px;
            border: 1px solid var(--landing-line);
            border-radius: 19px;
            background: rgba(255, 255, 255, .92);
            box-shadow:
                0 2px 4px rgba(132, 54, 62, .035),
                0 12px 25px rgba(132, 54, 62, .055);
            transition:
                border-color 180ms ease,
                box-shadow 180ms ease,
                transform 180ms ease;
        }

        .benefit-card:hover {
            border-color: #dba5a5;
            box-shadow: 0 18px 34px rgba(132, 54, 62, .10);
            transform: translateY(-4px);
        }

        .benefit-icon {
            display: grid;
            width: 47px;
            height: 47px;
            place-items: center;
            margin-bottom: 18px;
            color: var(--landing-brand);
            border: 1px solid #ebc9c8;
            border-radius: 13px;
            background: var(--landing-brand-soft);
            font-size: 20px;
        }

        .benefit-card:nth-child(2) .benefit-icon {
            color: #88631b;
            border-color: #ecd799;
            background: var(--landing-yellow-soft);
        }

        .benefit-card:nth-child(3) .benefit-icon {
            color: #496d64;
            border-color: #c9ddd7;
            background: #edf5f2;
        }

        .benefit-card h3 {
            margin: 0 0 8px;
            color: var(--landing-ink);
            font-size: 1.03rem;
        }

        .benefit-card p {
            margin: 0;
            color: var(--landing-muted);
            font-size: .83rem;
            line-height: 1.7;
        }

        /* ---------- CTA and footer ---------- */

        .bottom-cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            margin-top: 54px;
            padding: 28px 32px;
            border: 1px solid #ead28d;
            border-radius: 20px;
            background: var(--landing-yellow-soft);
        }

        .bottom-cta h2 {
            margin: 0 0 5px;
            color: var(--landing-ink);
            font-size: clamp(1.25rem, 3vw, 1.65rem);
        }

        .bottom-cta p {
            margin: 0;
            color: var(--landing-ink-soft);
            font-size: .84rem;
        }

        .bottom-cta-action {
            display: inline-flex;
            min-height: 46px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 19px;
            color: #fff;
            border: 1px solid var(--landing-brand);
            border-radius: 11px;
            background: var(--landing-brand);
            font-size: .86rem;
            font-weight: 700;
            text-decoration: none;
            transition:
                background-color 180ms ease,
                border-color 180ms ease,
                transform 180ms ease;
        }

        .bottom-cta-action:hover {
            color: #fff;
            border-color: var(--landing-brand-dark);
            background: var(--landing-brand-dark);
            transform: translateY(-2px);
        }

        .landing-footer {
            padding: 31px 0 28px;
            color: var(--landing-muted);
            font-size: .75rem;
            text-align: center;
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-7px);
            }
        }

        @media (max-width: 900px) {
            .hero-shell {
                padding: 48px 38px;
            }

            .hero-grid {
                grid-template-columns: 1fr;
            }

            .hero-copy {
                max-width: 690px;
            }

            .preview-wrap {
                width: min(100%, 500px);
                margin-inline: auto;
            }

            .benefit-grid {
                grid-template-columns: 1fr;
            }

            .benefit-card {
                display: grid;
                grid-template-columns: auto 1fr;
                column-gap: 17px;
            }

            .benefit-icon {
                grid-row: 1 / span 2;
                margin-bottom: 0;
            }
        }

        @media (max-width: 600px) {
            .landing-container {
                width: min(100% - 24px, 500px);
            }

            .landing-nav {
                padding: 12px 0;
            }

            .landing-nav-inner {
                min-height: 54px;
            }

            .landing-brand-icon {
                width: 36px;
                height: 36px;
            }

            .landing-brand-name {
                font-size: 1.08rem;
            }

            .nav-action {
                min-height: 37px;
                padding-inline: 13px;
            }

            .nav-action span {
                display: none;
            }

            .landing-main {
                padding: 16px 0 40px;
            }

            .hero-shell {
                padding: 38px 20px;
                border-radius: 22px;
            }

            .hero-grid {
                gap: 42px;
            }

            .hero-title {
                font-size: clamp(2.45rem, 13vw, 3.55rem);
            }

            .hero-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .primary-action,
            .secondary-action {
                width: 100%;
            }

            .preview-card {
                padding: 17px;
                transform: none;
            }

            .floating-note {
                position: relative;
                inset: auto !important;
                width: fit-content;
                margin-bottom: 10px;
                animation: none;
            }

            .floating-note.bottom {
                margin: 10px 0 0 auto;
            }

            .benefits-section {
                padding-top: 50px;
            }

            .section-heading {
                text-align: left;
            }

            .benefit-card {
                display: block;
                padding: 21px;
            }

            .benefit-icon {
                margin-bottom: 15px;
            }

            .bottom-cta {
                align-items: stretch;
                flex-direction: column;
                padding: 23px;
            }

            .bottom-cta-action {
                width: 100%;
            }
        }

        @media (max-width: 390px) {
            .hero-shell {
                padding-inline: 16px;
            }

            .preview-top {
                align-items: flex-start;
            }

            .preview-status {
                padding-inline: 7px;
            }

            .answer-option {
                font-size: .65rem;
            }
        }
    </style>
</head>

<body>
    <div class="landing-page">
        <header class="landing-nav">
            <div class="landing-container">
                <div class="landing-nav-inner">
                    <a href="index.php" class="landing-brand" aria-label="ExamHub beranda">
                        <span class="landing-brand-icon" aria-hidden="true">
                            <i class="bi bi-mortarboard-fill"></i>
                        </span>
                        <span class="landing-brand-name">Exam<span>Hub</span></span>
                    </a>

                    <a href="siswa/login_siswa.php" class="nav-action">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        <span>Masuk</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="landing-main">
            <div class="landing-container">
                <section class="hero-shell" aria-labelledby="hero-title">
                    <div class="hero-grid">
                        <div class="hero-copy">
                            <div class="eyebrow">
                                <i class="bi bi-stars" aria-hidden="true"></i>
                                Computer Based Testing
                            </div>

                            <h1 class="hero-title" id="hero-title">
                                Ujian digital,
                                <span class="highlight">lebih praktis.</span>
                            </h1>

                            <p class="hero-description">
                                ExamHub membantu guru mengelola ujian dan memudahkan siswa
                                mengerjakan soal secara terstruktur dalam satu platform.
                            </p>

                            <div class="hero-actions">
                                <a href="siswa/login_siswa.php" class="primary-action">
                                    Mulai sekarang
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>

                                <a href="#fitur" class="secondary-action">
                                    <i class="bi bi-grid" aria-hidden="true"></i>
                                    Lihat fitur
                                </a>
                            </div>

                            <div class="trust-note">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                Platform ujian untuk guru dan siswa
                            </div>
                        </div>

                        <div class="preview-wrap" aria-label="Contoh tampilan ujian ExamHub">
                            <div class="floating-note top">
                                <span class="floating-note-icon" aria-hidden="true">
                                    <i class="bi bi-journal-check"></i>
                                </span>
                                <span>
                                    <strong>Ujian Online</strong>
                                    <span>Mudah dan terstruktur</span>
                                </span>
                            </div>

                            <div class="preview-card">
                                <div class="preview-top">
                                    <div class="preview-label">
                                        <span class="preview-icon" aria-hidden="true">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </span>
                                        Pratinjau Ujian
                                    </div>

                                    <div class="preview-status">
                                        <span class="status-dot"></span>
                                        Siap
                                    </div>
                                </div>

                                <h2 class="preview-title">Latihan Ujian</h2>
                                <p class="preview-subtitle">Contoh antarmuka pengerjaan soal</p>

                                <div class="question-box">
                                    <div class="question-number">Pertanyaan 01</div>

                                    <p class="question-text">
                                        Apa manfaat utama dari sistem ujian berbasis web?
                                    </p>

                                    <div class="answer-option">
                                        <span class="answer-letter">A</span>
                                        Menambah penggunaan kertas
                                    </div>

                                    <div class="answer-option selected">
                                        <span class="answer-letter">B</span>
                                        Ujian lebih praktis dan terorganisir
                                        <i class="bi bi-check-circle-fill check" aria-hidden="true"></i>
                                    </div>

                                    <div class="answer-option">
                                        <span class="answer-letter">C</span>
                                        Membatasi akses informasi
                                    </div>

                                    <div class="answer-option">
                                        <span class="answer-letter">D</span>
                                        Menyimpan dokumen saja
                                    </div>
                                </div>

                                <div class="progress-info">
                                    <span>Progres soal</span>
                                    <span>1 dari 10</span>
                                </div>

                                <div class="progress-track" aria-label="Progres soal 65 persen">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>

                            <div class="floating-note bottom">
                                <span class="floating-note-icon" aria-hidden="true">
                                    <i class="bi bi-bar-chart-line"></i>
                                </span>
                                <span>
                                    <strong>Hasil Terorganisir</strong>
                                    <span>Penilaian dalam satu tempat</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="benefits-section" id="fitur" aria-labelledby="feature-title">
                    <div class="section-heading">
                        <div class="section-kicker">
                            <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                            Fitur ExamHub
                        </div>

                        <h2 class="section-title" id="feature-title">
                            Semua kebutuhan ujian dalam satu tempat
                        </h2>

                        <p class="section-description">
                            Proses pengelolaan ujian dibuat lebih ringkas untuk mendukung
                            kegiatan belajar dan evaluasi.
                        </p>
                    </div>

                    <div class="benefit-grid">
                        <article class="benefit-card">
                            <div class="benefit-icon" aria-hidden="true">
                                <i class="bi bi-journal-text"></i>
                            </div>
                            <h3>Kelola Ujian</h3>
                            <p>
                                Buat dan atur ujian beserta soal secara lebih rapi
                                melalui halaman khusus guru.
                            </p>
                        </article>

                        <article class="benefit-card">
                            <div class="benefit-icon" aria-hidden="true">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <h3>Ujian Berbasis Web</h3>
                            <p>
                                Siswa dapat mengakses dan mengerjakan ujian melalui
                                perangkat yang terhubung ke sistem.
                            </p>
                        </article>

                        <article class="benefit-card">
                            <div class="benefit-icon" aria-hidden="true">
                                <i class="bi bi-clipboard-data"></i>
                            </div>
                            <h3>Hasil Penilaian</h3>
                            <p>
                                Guru dapat meninjau nilai serta hasil pengerjaan siswa
                                dalam tampilan yang terstruktur.
                            </p>
                        </article>
                    </div>
                </section>

                <section class="bottom-cta" aria-label="Mulai menggunakan ExamHub">
                    <div>
                        <h2>Siap memulai ujian?</h2>
                        <p>Masuk sebagai siswa atau guru untuk menggunakan ExamHub.</p>
                    </div>

                    <a href="siswa/login_siswa.php" class="bottom-cta-action">
                        Masuk ke ExamHub
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </section>
            </div>
        </main>

        <footer class="landing-footer">
            <div class="landing-container">
                &copy; <?php echo date('Y'); ?> ExamHub. Computer Based Testing System.
            </div>
        </footer>
    </div>
</body>

</html>