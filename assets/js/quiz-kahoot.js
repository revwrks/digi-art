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
    }

    initAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContext();
        } catch (e) {
            this.audioCtx = null;
        }
    }

    toggleSound() {
        this.soundMuted = !this.soundMuted;
        localStorage.setItem('kahoot_muted', this.soundMuted);
        const btn = document.getElementById('kahootSoundToggle');
        if (btn) btn.textContent = this.soundMuted ? '🔇' : '🔊';
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
            const res = await fetch('api/quiz.php?action=get_questions');
            const data = await res.json();
            if (!data.success) {
                if (data.locked) {
                    this.container.innerHTML = `
                        <div class="locked-card">
                            <div class="lock-icon">🔒</div>
                            <h2>Fitur Kuis Terkunci</h2>
                            <p>${data.message}</p>
                            <button class="btn" onclick="openLoginModal()">Masuk Akun</button>
                        </div>
                    `;
                    return;
                }
                throw new Error(data.message);
            }

            this.questions = data.questions;
            this.showStartScreen();
        } catch (err) {
            this.container.innerHTML = `
                <div class="kahoot-error">
                    <h3>⚠️ Gagal memuat kuis</h3>
                    <p>${err.message}</p>
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
                    <div class="rule-item"><span>⚡</span> Cepat & Tepat (Hingga 1.000 Poin per Soal)</div>
                    <div class="rule-item"><span>🔥</span> Bonus Beruntun (Streak Multiplier)</div>
                    <div class="rule-item"><span>🏆</span> Papan Peringkat Kelas Realtime</div>
                </div>
                <button class="kahoot-btn-start" onclick="quizApp.startQuiz()">Mulai Sekarang →</button>
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
                    <div class="kahoot-streak-badge ${this.streak > 1 ? 'active' : ''}">🔥 Streak: ${this.streak}</div>
                    <div class="kahoot-score">Skor: <strong>${this.score.toLocaleString('id-ID')}</strong></div>
                    <button type="button" class="btn-sound-toggle" id="kahootSoundToggle" onclick="quizApp.toggleSound()" title="Aktif/Nonaktifkan Suara Kuis" style="background:#ffffff;border:1px solid #cbd5e1;font-size:16px;cursor:pointer;padding:4px 8px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.05)">
                        ${this.soundMuted ? '🔇' : '🔊'}
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

    selectAnswer(chosenIdx) {
        if (this.answered) return;
        this.answered = true;
        clearInterval(this.timer);

        const q = this.questions[this.currentIndex];
        const isCorrect = chosenIdx === parseInt(q.correct_index, 10);

        // Hitung Poin Kahoot
        let pointsEarned = 0;
        if (isCorrect) {
            this.correctCount++;
            this.streak++;
            if (this.streak > this.highestStreak) this.highestStreak = this.streak;

            // Skor dasar 500 + kecepatan hingga 500 + streak bonus
            const speedRatio = Math.max(0.1, this.timeLeft / this.totalTime);
            const speedBonus = Math.round(500 * speedRatio);
            const streakBonus = Math.min(500, (this.streak - 1) * 100);
            pointsEarned = 500 + speedBonus + streakBonus;
            this.score += pointsEarned;
            this.playCorrectSound();
        } else {
            this.streak = 0;
            this.playWrongSound();
        }

        this.showFeedback(isCorrect, chosenIdx, parseInt(q.correct_index, 10), pointsEarned);
    }

    timeOut() {
        this.answered = true;
        this.streak = 0;
        this.playWrongSound();
        const q = this.questions[this.currentIndex];
        this.showFeedback(false, -1, parseInt(q.correct_index, 10), 0, true);
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

        // Tampilkan modal feedback gaya Kahoot
        const overlay = document.createElement('div');
        overlay.className = `kahoot-feedback-overlay ${isCorrect ? 'bg-correct' : 'bg-wrong'}`;
        overlay.innerHTML = `
            <div class="feedback-card">
                <div class="feedback-icon">${isCorrect ? '✓' : (isTimeOut ? '⏰' : '✗')}</div>
                <h2>${isCorrect ? 'Luar Biasa! Jawaban Benar' : (isTimeOut ? 'Waktu Habis!' : 'Belum Tepat!')}</h2>
                <div class="feedback-points">+${points.toLocaleString('id-ID')} Poin</div>
                ${this.streak > 1 ? `<div class="feedback-streak">🔥 Streak Bonus x${this.streak}!</div>` : ''}
                <div class="feedback-total">Total Skor Saat Ini: <strong>${this.score.toLocaleString('id-ID')}</strong></div>
                <button class="kahoot-next-btn" onclick="quizApp.nextQuestion()">
                    ${this.currentIndex + 1 < this.questions.length ? 'Pertanyaan Berikutnya →' : 'Lihat Hasil Akhir 🏆'}
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
        this.currentOverlay = overlay;
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
                <div class="podium-header">🎉 Kuis Selesai!</div>
                <div class="podium-score">${this.score.toLocaleString('id-ID')}</div>
                <div class="podium-stats">
                    <div class="pstat"><span>Benar</span><strong>${this.correctCount} / ${this.questions.length}</strong></div>
                    <div class="pstat"><span>Streak Tertinggi</span><strong>🔥 ${this.highestStreak}</strong></div>
                </div>
                <div id="saveScoreStatus" class="podium-status">Menyimpan skor kuis ke papan peringkat…</div>
                <div class="kahoot-actions">
                    <button class="btn" onclick="quizApp.showStartScreen()">Ulangi Kuis 🔄</button>
                    <button class="btn secondary" onclick="quizApp.loadLeaderboard()">Lihat Papan Peringkat 🏆</button>
                </div>
                <div id="quizLeaderboardContainer" style="margin-top:28px"></div>
            </div>
        `;

        // Kirim skor ke server
        try {
            const res = await fetch('api/quiz.php?action=submit_score', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    total_score: this.score,
                    correct_count: this.correctCount,
                    total_questions: this.questions.length,
                    highest_streak: this.highestStreak
                })
            });
            const data = await res.json();
            const statusEl = document.getElementById('saveScoreStatus');
            if (statusEl) {
                if (data.success) {
                    statusEl.innerHTML = `✓ Skor tersimpan! Kamu berada di peringkat <strong>#${data.rank}</strong> di kelas.`;
                } else {
                    statusEl.textContent = 'Skor tercatat di sesi ini.';
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
            const res = await fetch('api/quiz.php?action=get_leaderboard');
            const data = await res.json();
            if (!data.success || !data.leaderboard.length) {
                box.innerHTML = '<p>Belum ada data nilai kuis.</p>';
                return;
            }

            box.innerHTML = `
                <div class="leaderboard-table-wrap">
                    <h3>🏆 Papan Peringkat Siswa</h3>
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
                                    <td>${idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : idx + 1))}</td>
                                    <td><strong>${this.escapeHTML(item.student_name)}</strong></td>
                                    <td>${this.escapeHTML(item.kelas || 'VIII')}</td>
                                    <td><span class="score-badge">${parseInt(item.total_score, 10).toLocaleString('id-ID')} pts</span></td>
                                    <td>${item.correct_count}/${item.total_questions}</td>
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
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
}

window.KahootQuiz = KahootQuiz;
