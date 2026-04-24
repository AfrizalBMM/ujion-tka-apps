function parseBuilderConfig() {
	const configElement = document.getElementById('personal-question-builder-config');
	if (!configElement) return null;

	try {
		return JSON.parse(configElement.textContent);
	} catch (error) {
		console.error('Failed to parse personal question builder config.', error);
		return null;
	}
}

function getDefaultQuestion() {
	return {
		tipe: 'PG',
		pertanyaan: '',
		opsi: [''],
		jawaban_benar: '',
		pembahasan: '',
		image_path: '',
		image_url: '',
		temp_preview_url: '',
		kategori: 'Umum',
		status: 'draft',
	};
}

function cleanupPreviewUrl(question) {
	if (question?.temp_preview_url) {
		URL.revokeObjectURL(question.temp_preview_url);
	}
}

function initPersonalQuestionBuilder() {
	const config = parseBuilderConfig();
	const root = document.getElementById('builder-app');

	if (!config || !root || typeof window.Vue !== 'function') return;

	const initialQuestions = Array.isArray(config.initialQuestions) && config.initialQuestions.length
		? config.initialQuestions
		: [getDefaultQuestion()];

	new window.Vue({
		el: '#builder-app',
		data: {
			questions: initialQuestions,
			current: 0,
			isSaving: false,
			isUploadingImage: false,
			saveError: '',
			saveSuccess: '',
		},
		methods: {
			go(idx) {
				this.current = idx;
			},
			next() {
				if (this.current < this.questions.length - 1) {
					this.current += 1;
				}
			},
			prev() {
				if (this.current > 0) {
					this.current -= 1;
				}
			},
			add() {
				this.questions.push(getDefaultQuestion());
				this.current = this.questions.length - 1;
			},
			remove(idx) {
				const question = this.questions[idx];
				cleanupPreviewUrl(question);

				this.questions.splice(idx, 1);

				if (this.current >= this.questions.length) {
					this.current = this.questions.length - 1;
				}
			},
			addOpsi() {
				if ((this.questions[this.current].opsi || []).length >= 5) return;
				this.questions[this.current].opsi.push('');
			},
			removeOpsi(i) {
				this.questions[this.current].opsi.splice(i, 1);

				const answerKey = this.questions[this.current].jawaban_benar || '';
				const validLabels = (this.questions[this.current].opsi || [])
					.slice(0, 5)
					.map((_, idx) => String.fromCharCode(65 + idx));

				if (
					['PG', 'Checklist'].includes(this.questions[this.current].tipe) &&
					answerKey &&
					!validLabels.includes(answerKey)
				) {
					this.questions[this.current].jawaban_benar = '';
				}
			},
			previewSoal(question) {
				let html = `<b>${question.pertanyaan}</b><br>`;

				if (['PG', 'Checklist'].includes(question.tipe)) {
					html += `<ul>${(question.opsi || []).map((opsi) => `<li>${opsi}</li>`).join('')}</ul>`;
				}

				const imageSrc = question.temp_preview_url || question.image_url || question.image_path || '';
				if (imageSrc) {
					html += `<img src="${imageSrc}" class="max-h-32">`;
				}

				setTimeout(() => {
					document.querySelectorAll('.katex-math').forEach((element) => {
						try {
							window.katex?.render(element.textContent, element, { throwOnError: false });
						} catch (error) {
							console.error('Failed to render KaTeX preview.', error);
						}
					});
				}, 10);

				return html.replace(/\$\$(.*?)\$\$/g, '<span class="katex-math">$1</span>');
			},
			async pickImage(event) {
				const file = event?.target?.files?.[0] || null;
				if (!file) return;

				this.saveError = '';
				this.saveSuccess = '';

				if (file.size > 2 * 1024 * 1024) {
					this.saveError = 'Ukuran gambar maksimal 2 MB.';
					event.target.value = '';
					return;
				}

				const currentQuestion = this.questions[this.current];
				cleanupPreviewUrl(currentQuestion);
				currentQuestion.temp_preview_url = URL.createObjectURL(file);
				this.isUploadingImage = true;

				const formData = new FormData();
				formData.append('image', file);

				try {
					const response = await fetch(config.uploadImageUrl, {
						method: 'POST',
						headers: {
							Accept: 'application/json',
							'X-Requested-With': 'XMLHttpRequest',
							'X-CSRF-TOKEN': config.csrfToken,
						},
						body: formData,
					});

					if (!response.ok) {
						let payload = null;

						try {
							payload = await response.json();
						} catch (error) {
							payload = null;
						}

						if (payload?.errors) {
							const firstKey = Object.keys(payload.errors)[0];
							this.saveError = (payload.errors[firstKey] || [])[0] || 'Gagal upload gambar.';
						} else {
							this.saveError = 'Gagal upload gambar.';
						}

						return;
					}

					const payload = await response.json();
					currentQuestion.image_path = payload.path || '';
					currentQuestion.image_url = payload.url || '';
				} catch (error) {
					this.saveError = 'Gagal upload gambar. Coba lagi.';
				} finally {
					this.isUploadingImage = false;
					event.target.value = '';
				}
			},
			clearImage() {
				const currentQuestion = this.questions[this.current];
				cleanupPreviewUrl(currentQuestion);
				currentQuestion.image_path = '';
				currentQuestion.image_url = '';
				currentQuestion.temp_preview_url = '';
			},
			save() {
				this.saveError = '';
				this.saveSuccess = '';
				this.isSaving = true;

				const payloadQuestions = this.questions.map((question) => ({
					tipe: question.tipe,
					pertanyaan: question.pertanyaan,
					opsi: question.opsi,
					jawaban_benar: question.jawaban_benar,
					pembahasan: question.pembahasan,
					image_path: question.image_path || '',
					kategori: question.kategori,
					status: question.status,
				}));

				fetch(config.saveUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
						'X-CSRF-TOKEN': config.csrfToken,
					},
					body: JSON.stringify({ questions: payloadQuestions }),
				})
					.then(async (response) => {
						if (!response.ok) {
							let payload = null;

							try {
								payload = await response.json();
							} catch (error) {
								payload = null;
							}

							if (payload?.errors) {
								const firstKey = Object.keys(payload.errors)[0];
								const firstMessage = (payload.errors[firstKey] || [])[0] || 'Gagal menyimpan.';
								this.saveError = firstMessage;
							} else {
								this.saveError = 'Gagal menyimpan. Pastikan semua field wajib terisi.';
							}

							return;
						}

						const payload = await response.json().catch(() => ({}));
						this.saveSuccess = payload.message || 'Soal berhasil disimpan.';
						setTimeout(() => window.location.reload(), 450);
					})
					.catch(() => {
						this.saveError = 'Gagal menyimpan. Periksa koneksi atau coba lagi.';
					})
					.finally(() => {
						this.isSaving = false;
					});
			},
		},
	});
}

document.addEventListener('DOMContentLoaded', initPersonalQuestionBuilder);
