<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
	open: {
		type: Boolean,
		default: false,
	},
	// Normal price for the pembahasan access (e.g. 5000)
	basePrice: {
		type: Number,
		default: 5000,
	},
	// Order token for Doku payment redirect
	orderToken: {
		type: String,
		default: '',
	},
});

const emit = defineEmits(['close', 'verified']);

// State
const couponCode = ref('');
const verifying = ref(false);
const verified = ref(false);
const error = ref('');
const discountAmount = ref(0);
const finalPrice = ref(props.basePrice);

const formatRupiah = (value) => 'Rp' + Number(value).toLocaleString('id-ID');

const computedFinalPrice = computed(() => Math.max(props.basePrice - discountAmount.value, 0));

const discountPercent = computed(() => {
	if (props.basePrice <= 0) return 0;
	return Math.round((discountAmount.value / props.basePrice) * 100);
});

const resetState = () => {
	couponCode.value = '';
	verifying.value = false;
	verified.value = false;
	error.value = '';
	discountAmount.value = 0;
	finalPrice.value = props.basePrice;
};

const closeModal = () => {
	resetState();
	emit('close');
};

const verifyCoupon = async () => {
	if (!couponCode.value.trim()) {
		error.value = 'Masukkan kode kupon terlebih dahulu.';
		return;
	}

	verifying.value = true;
	error.value = '';
	verified.value = false;

	try {
		// ASSUMPTION: endpoint POST /ujian-online/coupon/verify
		// Expects: { code, order_token, base_price }
		// Returns: { ok: true, discount_amount, final_price } or { ok: false, message }
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
		const response = await fetch('/ujian-online/coupon/verify', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': csrfToken,
				'Accept': 'application/json',
			},
			body: JSON.stringify({
				code: couponCode.value.trim().toUpperCase(),
				order_token: props.orderToken,
				base_price: props.basePrice,
			}),
		});

		const data = await response.json();

		if (data.ok) {
			discountAmount.value = Number(data.discount_amount) || 0;
			finalPrice.value = Number(data.final_price) || computedFinalPrice.value;
			verified.value = true;
			emit('verified', {
				code: couponCode.value.trim().toUpperCase(),
				discount: discountAmount.value,
				finalPrice: finalPrice.value,
			});
		} else {
			error.value = data.message || 'Kupon tidak valid.';
		}
	} catch (e) {
		error.value = 'Terjadi kesalahan. Silakan coba lagi.';
	} finally {
		verifying.value = false;
	}
};

const payNow = () => {
	// Redirect to Doku payment using existing payment start flow
	// The existing JS (doku-checkout.js) handles the payment initiation
	// We emit the pay event and let parent handle redirect
	if (props.orderToken) {
		emit('pay', {
			orderToken: props.orderToken,
			couponCode: verified.value ? couponCode.value.trim().toUpperCase() : null,
			finalPrice: finalPrice.value,
		});
	}
};
</script>

<template>
	<div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal" @keydown.escape="closeModal" tabindex="-1">
		<!-- Overlay -->
		<div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

		<!-- Modal -->
		<div class="relative w-full max-w-md overflow-hidden rounded-[28px] bg-white shadow-modal dark:bg-slate-900">
			<!-- Header -->
			<div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-3">
						<div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-primary">
							<i class="fa-solid fa-ticket"></i>
						</div>
						<div>
							<h3 class="text-lg font-bold text-slate-900 dark:text-white">Akses Pembahasan</h3>
							<p class="text-xs text-textSecondary dark:text-slate-400">Lihat kunci jawaban & pembahasan lengkap</p>
						</div>
					</div>
					<button type="button" class="icon-button" @click="closeModal" aria-label="Tutup">
						<i class="fa-solid fa-xmark"></i>
					</button>
				</div>
			</div>

			<!-- Body -->
			<div class="px-6 py-6 space-y-5">
				<!-- Base Price -->
				<div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/50">
					<div class="flex items-center justify-between">
						<span class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga Normal</span>
						<span class="text-xl font-black text-slate-900 dark:text-white">{{ formatRupiah(basePrice) }}</span>
					</div>
				</div>

				<!-- Coupon Form -->
				<div v-if="!verified" class="space-y-3">
					<label for="coupon-code" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
						Punya kode kupon?
					</label>
					<div class="flex gap-2">
						<input
							id="coupon-code"
							v-model="couponCode"
							type="text"
							placeholder="Masukkan kode kupon"
							maxlength="30"
							class="flex-1 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-semibold uppercase text-slate-800 transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100"
							@keydown.enter="verifyCoupon"
							:disabled="verifying"
						>
						<button
							type="button"
							@click="verifyCoupon"
							:disabled="verifying"
							class="btn-primary px-5 py-3 text-sm font-bold whitespace-nowrap"
						>
							<span v-if="verifying" class="flex items-center gap-2">
								<i class="fa-solid fa-spinner fa-spin"></i>
								Verifikasi...
							</span>
							<span v-else>Verifikasi</span>
						</button>
					</div>

					<!-- Error Message -->
					<div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
						<div class="flex items-start gap-2">
							<i class="fa-solid fa-circle-exclamation mt-0.5"></i>
							<span>{{ error }}</span>
						</div>
					</div>
				</div>

				<!-- Verified State -->
				<div v-if="verified" class="space-y-3">
					<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
						<div class="flex items-center gap-3">
							<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500 text-white">
								<i class="fa-solid fa-check"></i>
							</div>
							<div>
								<div class="text-sm font-bold text-emerald-700 dark:text-emerald-300">Kupon diterapkan!</div>
								<div class="text-xs text-emerald-600 dark:text-emerald-400">
									Kode <code class="font-bold">{{ couponCode.toUpperCase() }}</code> — Hemat {{ formatRupiah(discountAmount) }}
								</div>
							</div>
						</div>
					</div>

					<!-- Final Price -->
					<div class="rounded-2xl border border-primary/30 bg-primary/5 p-4">
						<div class="flex items-center justify-between">
							<div>
								<span class="text-xs font-bold uppercase tracking-widest text-textSecondary">Harga Final</span>
								<div v-if="discountAmount > 0" class="text-xs text-slate-400 line-through">{{ formatRupiah(basePrice) }}</div>
							</div>
							<span class="text-2xl font-black text-primary">{{ formatRupiah(finalPrice) }}</span>
						</div>
					</div>

					<!-- Option to change coupon -->
					<button type="button" class="text-xs font-semibold text-textSecondary hover:text-primary" @click="resetState">
						<i class="fa-solid fa-rotate-left mr-1"></i> Ganti kode kupon
					</button>
				</div>
			</div>

			<!-- Footer -->
			<div class="border-t border-slate-100 px-6 py-5 dark:border-slate-800">
				<div class="flex items-center justify-between gap-3">
					<div class="text-sm">
						<div class="font-bold text-slate-900 dark:text-white">{{ formatRupiah(finalPrice) }}</div>
						<div class="text-xs text-textSecondary">Total bayar</div>
					</div>
					<button type="button" class="btn-primary px-6 py-3 text-sm font-bold" @click="payNow">
						<i class="fa-solid fa-bolt mr-2"></i>
						Bayar Sekarang
					</button>
				</div>
			</div>
		</div>
	</div>
</template>
