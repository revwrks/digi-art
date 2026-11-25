/**
 * Kahoot!-Style Interactive Quiz Engine
 * Fitur:
 * - 4 Tombol Warna & Simbol Khas: ▲ Merah, ◆ Biru, ● Kuning, ■ Hijau
 * - Audio Sintesis Web Audio API (Tanpa file berat untuk hosting gratis)
 * - Timer Countdown dinamis & kalkulasi skor berbasis kecepatan
 * - Streak Bonus (+100 poin beruntun)
 * - Layar Feedback instan & Podium Papan Peringkat (Leaderboard)
 */

class KahootQuiz {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.questions = [];
        this.currentIndex = 0;
        this.score = 0;
        this.streak = 0;
        this.highestStreak = 0;
        this.correctCount = 0;
        this.timer = null;
        this.timeLeft = 20;
        this.totalTime = 20;
        this.answered = false;
        this.audioCtx = null;
        this.soundMuted = localStorage.getItem('kahoot_muted') === 'true';

        this.initAudio();
        this.bindKeyboard();
    }

    bindKeyboard() {
        if (this._keyboardBound) return;
        this._keyboardBound = true;

        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            const activeTag = document.activeElement?.tagName;
            if (
                activeTag === 'INPUT' ||
                activeTag === 'TEXTAREA' ||
                activeTag === 'SELECT' ||
                document.activeElement?.isContentEditable
            ) {
                return;
            }

            if (this.currentOverlay) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.nextQuestion();
                }
                return;
            }

            if (
                !this.answered &&
                this.questions.length > 0 &&
                this.currentIndex < this.questions.length &&
                document.getElementById('optBtn_0')
            ) {
                const keyMap = { '1': 0, '2': 1, '3': 2, '4': 3 };
                if (Object.prototype.hasOwnProperty.call(keyMap, e.key)) {
                    e.preventDefault();
                    this.selectAnswer(keyMap[e.key]);
                }
            }
        });
    }

    initAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        } catch (e) {
            this.audioCtx = null;
        }
    }

    renderSoundButtonContent() {
        if (this.soundMuted) {
            return `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                <span>Bisukan</span>
            `;
        }
        return `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
            <span>Suara Aktif</span>
        `;
    }

    toggleSound() {
        this.soundMuted = !this.soundMuted;
        localStorage.setItem('kahoot_muted', this.soundMuted);
        const btn = document.getElementById('kahootSoundToggle');
        if (btn) {
            btn.innerHTML = this.renderSoundButtonContent();
            btn.setAttribute('aria-pressed', this.soundMuted ? 'true' : 'false');
        }
        if (!this.soundMuted) {
            this.playBeep(523.25, 'triangle', 0.1);
        }
    }

    playBeep(freq, type = 'sine', duration = 0.15, vol = 0.2) {
        if (this.soundMuted || !this.audioCtx) return;
        try {
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime);
            gain.gain.setValueAtTime(vol, this.audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(this.audioCtx.destination);
            osc.start();
            osc.stop(this.audioCtx.currentTime + duration);
        } catch (e) {}
    }

    playTickSound(sec) {
        if (this.soundMuted) return;
        // Frekuensi semakin tinggi di 5 detik terakhir untuk sensasi ketegangan kuis
        const freq = 660 + (5 - sec) * 110;
        this.playBeep(freq, 'sine', 0.05, 0.25);
    }

    playCorrectSound() {
        if (this.soundMuted || !this.audioCtx) return;
        // 4-Note Ascending Chime: C5 - E5 - G5 - C6
        this.playBeep(523.25, 'triangle', 0.08, 0.2);
        setTimeout(() => this.playBeep(659.25, 'triangle', 0.08, 0.2), 90);
        setTimeout(() => this.playBeep(783.99, 'triangle', 0.08, 0.2), 180);
        setTimeout(() => this.playBeep(1046.50, 'sine', 0.25, 0.25), 270);
    }

    playWrongSound() {
        if (this.soundMuted || !this.audioCtx) return;
        this.playBeep(220, 'sawtooth', 0.15, 0.25);
        setTimeout(() => this.playBeep(180, 'sawtooth', 0.35, 0.3), 130);
    }

    playFanfare() {
        if (this.soundMuted || !this.audioCtx) return;
        const notes = [
            { f: 523.25, d: 0.12, delay: 0 },
            { f: 659.25, d: 0.12, delay: 130 },
            { f: 783.99, d: 0.12, delay: 260 },
            { f: 1046.50, d: 0.45, delay: 390 }
        ];
        notes.forEach(n => {
            setTimeout(() => this.playBeep(n.f, 'triangle', n.d, 0.28), n.delay);
        });
    }

    async loadQuestions() {
        this.container.innerHTML = `
            <div class="kahoot-loader">
                <div class="kahoot-spinner"></div>
                <h2>Memuat Soal Kuis…</h2>
            </div>
        `;
        try {
            const res = await App.apiFetch('api/quiz.php?action=get_questions');
            const data = await res.json();
            if (!data.success) {
                if (data.locked) {
                    this.container.innerHTML = `
                        <div class="locked-card">
                            <div class="lock-icon" aria-hidden="true">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <h2>Fitur Kuis Terkunci</h2>
                            <p>${this.escapeHTML(data.message || 'Kuis hanya dapat diakses oleh akun Siswa dan Guru.')}</p>
                            <button class="btn" onclick="openLoginModal()">Masuk Akun</button>
                        </div>
                    `;
                    return;
                }
                throw new Error(data.message || 'Gagal memuat soal kuis.');
            }

            this.questions = data.questions || [];
            this.showStartScreen();
        } catch (err) {
            this.container.innerHTML = `
                <div class="kahoot-error">
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:#fef2f2;color:#b91c1c;margin-bottom:10px" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <h3>Gagal memuat kuis</h3>
                    <p>${this.escapeHTML(err.message || 'Terjadi kesalahan saat menghubungi server.')}</p>
                    <button class="btn" onclick="quizApp.loadQuestions()">Coba Lagi</button>
                </div>
            `;
        }
    }

    showStartScreen() {
        this.currentIndex = 0;
        this.score = 0;
        this.streak = 0;
        this.highestStreak = 0;
        this.correctCount = 0;

        this.container.innerHTML = `
            <div class="kahoot-start-card">
                <div class="kahoot-badge">Kuis Pemahaman Seni Rupa</div>
                <h1>Digi-Art Challenge!</h1>
                <p>Uji pemahamanmu tentang Menggambar Ilustrasi. Semakin cepat menjawab benar, semakin tinggi poin yang didapat!</p>
                <div class="kahoot-rules">
                    <div class="rule-item">
                        <span aria-hidden="true" style="display:inline-flex;color:var(--primary)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </span>
                        Cepat &amp; Tepat (Hingga 1.000 Poin per Soal)
                    </div>
                    <div class="rule-item">
                        <span aria-hidden="true" style="display:inline-flex;color:var(--accent-amber)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </span>
                        Bonus Beruntun (Streak Multiplier)
                    </div>
                    <div class="rule-item">
                        <span aria-hidden="true" style="display:inline-flex;color:var(--accent-emerald)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                        </span>
                        Papan Peringkat Kelas Realtime
                    </div>
                </div>
                <button class="kahoot-btn-start" onclick="quizApp.startQuiz()">
                    Mulai Sekarang
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </div>
        `;
    }

    startQuiz() {
        this.currentIndex = 0;
        this.renderQuestion();
    }

    renderQuestion() {
        if (this.currentIndex >= this.questions.length) {
            this.finishQuiz();
            return;
        }

        const q = this.questions[this.currentIndex];
        this.totalTime = q.time_limit || 20;
        this.timeLeft = this.totalTime;
        this.answered = false;

        const shapes = [
            { icon: '▲', color: 'kahoot-red', key: 'option_a', idx: 0 },
            { icon: '◆', color: 'kahoot-blue', key: 'option_b', idx: 1 },
            { icon: '●', color: 'kahoot-yellow', key: 'option_c', idx: 2 },
            { icon: '■', color: 'kahoot-green', key: 'option_d', idx: 3 }
        ];

        this.container.innerHTML = `
            <div class="kahoot-stage">
                <!-- Top Status Bar -->
                <div class="kahoot-topbar">
                    <div class="kahoot-qnum">Soal ${this.currentIndex + 1} / ${this.questions.length}</div>
                    <div class="kahoot-streak-badge ${this.streak > 1 ? 'active' : ''}">Streak: ${this.streak}</div>
                    <div class="kahoot-score">Skor: <strong>${this.score.toLocaleString('id-ID')}</strong></div>
                    <button type="button" class="btn-sound-toggle" id="kahootSoundToggle" onclick="quizApp.toggleSound()" title="Aktif/Nonaktifkan Suara Kuis" aria-pressed="${this.soundMuted ? 'true' : 'false'}" style="background:#ffffff;border:1px solid #cbd5e1;font-size:12px;font-weight:700;cursor:pointer;padding:6px 12px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.05);gap:6px">
                        ${this.renderSoundButtonContent()}
                    </button>
                </div>

                <!-- Timer Bar -->
                <div class="kahoot-timer-wrap">
                    <div class="kahoot-timer-bar" id="kahootTimerBar"></div>
                    <div class="kahoot-timer-sec" id="kahootTimerText">${this.timeLeft}s</div>
                </div>

                <!-- Question Title -->
                <div class="kahoot-question-box">
                    <h2>${this.escapeHTML(q.question)}</h2>
                </div>

                <!-- 4 Colored Answer Buttons -->
                <div class="kahoot-options-grid">
                    ${shapes.map(s => `
                        <button class="kahoot-opt ${s.color}" id="optBtn_${s.idx}" onclick="quizApp.selectAnswer(${s.idx})">
                            <span class="kahoot-shape">${s.icon}</span>
                            <span class="kahoot-opt-text">${this.escapeHTML(q[s.key])}</span>
                        </button>
                    `).join('')}
                </div>
            </div>
        `;

        this.startTimer();
    }

    startTimer() {
        clearInterval(this.timer);
        const timerBar = document.getElementById('kahootTimerBar');
        const timerText = document.getElementById('kahootTimerText');

        this.timer = setInterval(() => {
            this.timeLeft--;
            if (timerText) timerText.textContent = `${this.timeLeft}s`;
            if (timerBar) {
                const percent = (this.timeLeft / this.totalTime) * 100;
                timerBar.style.width = `${percent}%`;
                if (percent < 25) {
                    timerBar.style.background = '#e74c3c';
                } else if (percent < 50) {
                    timerBar.style.background = '#f39c12';
                }
            }

            // Ketukan detik Web Audio di 5 detik terakhir
            if (this.timeLeft <= 5 && this.timeLeft > 0) {
                this.playTickSound(this.timeLeft);
            }

            if (this.timeLeft <= 0) {
                clearInterval(this.timer);
                this.timeOut();
            }
        }, 1000);
    }

    disableOptionButtons() {
        for (let i = 0; i < 4; i++) {
            const btn = document.getElementById(`optBtn_${i}`);
            if (btn) btn.disabled = true;
        }
    }

    async selectAnswer(chosenIdx) {
        if (this.answered) return;
        this.answered = true;
        clearInterval(this.timer);
        this.disableOptionButtons();

        const q = this.questions[this.currentIndex];
        if (!q) return;

        try {
            const response = await App.apiFetch('api/quiz.php?action=check_answer', {
                method: 'POST',
                body: JSON.stringify({
                    question_id: q.id,
                    chosen_index: chosenIdx,
                    time_left: this.timeLeft
                })
            });
            const res = await response.json();
            if (!res.success) {
                throw new Error(res.message || 'Gagal memeriksa jawaban.');
            }

            this.score = parseInt(res.total_score, 10) || 0;
            this.streak = parseInt(res.streak, 10) || 0;
            this.highestStreak = parseInt(res.highest_streak, 10) || this.highestStreak;
            this.correctCount = parseInt(res.correct_count, 10) || 0;

            if (res.is_correct) {
                this.playCorrectSound();
            } else {
                this.playWrongSound();
            }

            this.showFeedback(
                Boolean(res.is_correct),
                chosenIdx,
                parseInt(res.correct_index, 10),
                parseInt(res.points_earned, 10) || 0,
                false
            );
        } catch (err) {
            this.playWrongSound();
            window.showToast?.(err.message || 'Gagal memverifikasi jawaban.');
            this.showFeedback(false, chosenIdx, -1, 0, false);
        }
    }

    async timeOut() {
        if (this.answered) return;
        this.answered = true;
        clearInterval(this.timer);
        this.disableOptionButtons();

        const q = this.questions[this.currentIndex];
        if (!q) return;

        let correctIdx = -1;
        try {
            const response = await App.apiFetch('api/quiz.php?action=check_answer', {
                method: 'POST',
                body: JSON.stringify({
                    question_id: q.id,
                    chosen_index: -1,
                    time_left: 0
                })
            });
            const res = await response.json();
            if (res.success) {
                this.score = parseInt(res.total_score, 10) || 0;
                this.streak = parseInt(res.streak, 10) || 0;
                this.highestStreak = parseInt(res.highest_streak, 10) || this.highestStreak;
                this.correctCount = parseInt(res.correct_count, 10) || 0;
                correctIdx = parseInt(res.correct_index, 10);
            }
        } catch (err) {
            this.streak = 0;
        }

        this.playWrongSound();
        this.showFeedback(false, -1, correctIdx, 0, true);
    }

    showFeedback(isCorrect, chosenIdx, correctIdx, points, isTimeOut = false) {
        // Sorot tombol di layar
        for (let i = 0; i < 4; i++) {
            const btn = document.getElementById(`optBtn_${i}`);
            if (!btn) continue;
            if (i === correctIdx) {
                btn.classList.add('is-correct');
            } else if (i === chosenIdx) {
                btn.classList.add('is-wrong');
            } else {
                btn.classList.add('is-dim');
            }
        }

        let iconSvg = '';
        if (isCorrect) {
            iconSvg = `<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>`;
        } else if (isTimeOut) {
            iconSvg = `<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
        } else {
            iconSvg = `<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
        }

        const isLast = this.currentIndex + 1 >= this.questions.length;

        // Tampilkan modal feedback gaya Kahoot
        const overlay = document.createElement('div');
        overlay.className = `kahoot-feedback-overlay ${isCorrect ? 'bg-correct' : 'bg-wrong'}`;
        overlay.innerHTML = `
            <div class="feedback-card" role="dialog" aria-modal="true">
                <div class="feedback-icon">${iconSvg}</div>
                <h2>${isCorrect ? 'Luar Biasa! Jawaban Benar' : (isTimeOut ? 'Waktu Habis!' : 'Belum Tepat!')}</h2>
                <div class="feedback-points">+${(parseInt(points, 10) || 0).toLocaleString('id-ID')} Poin</div>
                ${this.streak > 1 ? `<div class="feedback-streak">Streak Bonus x${this.streak}</div>` : ''}
                <div class="feedback-total">Total Skor Saat Ini: <strong>${this.score.toLocaleString('id-ID')}</strong></div>
                <button class="kahoot-next-btn" onclick="quizApp.nextQuestion()">
                    ${isLast ? 'Lihat Hasil Akhir' : 'Pertanyaan Berikutnya'}
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
        this.currentOverlay = overlay;
        overlay.querySelector('.kahoot-next-btn')?.focus();
    }

    nextQuestion() {
        if (this.currentOverlay) {
            this.currentOverlay.remove();
            this.currentOverlay = null;
        }
        this.currentIndex++;
        this.renderQuestion();
    }

    async finishQuiz() {
        this.playFanfare();
        this.container.innerHTML = `
            <div class="kahoot-result-card">
                <div class="podium-header">Kuis Selesai!</div>
                <div class="podium-score">${this.score.toLocaleString('id-ID')}</div>
                <div class="podium-stats">
                    <div class="pstat"><span>Benar</span><strong>${this.correctCount} / ${this.questions.length}</strong></div>
                    <div class="pstat"><span>Streak Tertinggi</span><strong>${this.highestStreak}</strong></div>
                </div>
                <div id="saveScoreStatus" class="podium-status">Menyimpan skor kuis ke papan peringkat…</div>
                <div class="kahoot-actions">
                    <button class="btn" onclick="quizApp.loadQuestions()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        Ulangi Kuis
                    </button>
                    <button class="btn secondary" onclick="quizApp.loadLeaderboard()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                        Lihat Papan Peringkat
                    </button>
                </div>
                <div id="quizLeaderboardContainer" style="margin-top:28px"></div>
            </div>
        `;

        // Kirim permintaan simpan skor terverifikasi ke server
        try {
            const res = await App.apiFetch('api/quiz.php?action=submit_score', {
                method: 'POST',
                body: JSON.stringify({})
            });
            const data = await res.json();
            const statusEl = document.getElementById('saveScoreStatus');
            if (statusEl) {
                if (data.success) {
                    const rankNum = parseInt(data.rank, 10) || 1;
                    statusEl.innerHTML = `Skor tersimpan! Kamu berada di peringkat <strong>#${rankNum}</strong> di kelas.`;
                } else {
                    statusEl.textContent = data.message || 'Skor tercatat di sesi ini.';
                }
            }
        } catch (e) {
            const statusEl = document.getElementById('saveScoreStatus');
            if (statusEl) statusEl.textContent = 'Skor tercatat di perangkat.';
        }
    }

    async loadLeaderboard() {
        const box = document.getElementById('quizLeaderboardContainer');
        if (!box) return;
        box.innerHTML = '<p>Memuat papan peringkat…</p>';
        try {
            const res = await App.apiFetch('api/quiz.php?action=get_leaderboard');
            const data = await res.json();
            if (!data.success || !data.leaderboard.length) {
                box.innerHTML = '<p>Belum ada data nilai kuis.</p>';
                return;
            }

            box.innerHTML = `
                <div class="leaderboard-table-wrap">
                    <h3 style="display:flex;align-items:center;justify-content:center;gap:8px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                        Papan Peringkat Siswa
                    </h3>
                    <table class="kahoot-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Skor</th>
                                <th>Akurasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.leaderboard.map((item, idx) => `
                                <tr class="${idx < 3 ? 'top-rank rank-' + (idx + 1) : ''}">
                                    <td><strong>${idx + 1}.</strong></td>
                                    <td><strong>${this.escapeHTML(item.student_name)}</strong></td>
                                    <td>${this.escapeHTML(item.kelas || 'VIII')}</td>
                                    <td><span class="score-badge">${(parseInt(item.total_score, 10) || 0).toLocaleString('id-ID')} pts</span></td>
                                    <td>${parseInt(item.correct_count, 10) || 0}/${parseInt(item.total_questions, 10) || 0}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } catch (e) {
            box.innerHTML = '<p>Gagal memuat papan peringkat.</p>';
        }
    }

    escapeHTML(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
}

window.KahootQuiz = KahootQuiz;
