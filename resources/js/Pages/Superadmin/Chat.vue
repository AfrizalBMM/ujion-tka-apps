<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';

const page = usePage();

const props = defineProps({
	chats: {
		type: Array,
		default: () => [],
	},
	users: {
		type: Array,
		required: true,
	},
	selectedUser: {
		type: Object,
		default: null,
	},
	chatPaginator: {
		type: Object,
		default: null,
	},
});

const authUserId = computed(() => page.props.auth?.user?.id);
const authAvatarUrl = computed(
	() => page.props.auth?.user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin&background=4F6EF7&color=fff'
);

const isOwn = (chat) => Number(chat.from_user_id) === Number(authUserId.value);

const senderAvatarUrl = (chat) =>
	chat.from_user?.avatar_url || 'https://ui-avatars.com/api/?name=User&background=22C1C3&color=fff';

const accountDetailOpen = ref(false);

const messageForm = useForm({
	to_user_id: props.selectedUser?.id ?? '',
	message: '',
	image: null,
});

const imagePreviewUrl = ref('');
const imagePreviewMeta = ref('');

const onImageChange = (event) => {
	const file = event.target.files?.[0] || null;
	messageForm.image = file;

	if (!file) {
		clearImagePreview();
		return;
	}

	if (imagePreviewUrl.value) {
		URL.revokeObjectURL(imagePreviewUrl.value);
	}

	imagePreviewUrl.value = URL.createObjectURL(file);
	const sizeKb = Math.max(1, Math.round(file.size / 1024));
	imagePreviewMeta.value = `${file.name} (${sizeKb} KB)`;
};

const clearImagePreview = () => {
	if (imagePreviewUrl.value) {
		URL.revokeObjectURL(imagePreviewUrl.value);
	}
	imagePreviewUrl.value = '';
	imagePreviewMeta.value = '';
	messageForm.image = null;
	const input = document.getElementById('chat-image-input');
	if (input) input.value = '';
};

const sendMessage = () => {
	messageForm.post(route('superadmin.chat.store'), {
		preserveScroll: true,
		onSuccess: () => {
			messageForm.reset('message', 'image');
			clearImagePreview();
		},
	});
};
</script>

<template>
	<Head title="Live Chat" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div>
				<h1 class="text-2xl font-bold">Live Chat Guru/Operator</h1>
			</div>
			<div class="grid gap-6 md:grid-cols-3">
				<div class="md:col-span-1">
					<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-card p-4 flex flex-col gap-4 h-full">
						<div class="flex items-center gap-2 mb-2">
							<i class="fa-solid fa-users text-primary"></i>
							<span class="font-bold text-base">Daftar Guru</span>
							<div class="flex-1"></div>
							<form v-if="users.length" method="POST" :action="route('superadmin.chat.destroyAllGuru')" onsubmit="return false;" id="delete-all-chats-guru-form">
								<input type="hidden" name="_token" :value="page.props.csrf_token">
								<button
									type="submit"
									class="icon-button btn-danger btn-xs"
									data-confirm="Semua pesan dan gambar dari seluruh guru akan dihapus permanen. Lanjutkan?"
									data-confirm-title="Hapus Semua Pesan Semua Guru?"
									title="Hapus semua pesan guru"
								>
									<i class="fa-solid fa-trash"></i>
								</button>
							</form>
						</div>
						<div class="flex-1 overflow-y-auto pr-1">
							<template v-if="users.length > 0">
								<Link
									v-for="user in users"
									:key="user.id"
									:href="route('superadmin.chat.index', { user: user.id })"
									class="flex items-center gap-3 rounded-xl px-3 py-2 mb-1 transition"
									:class="selectedUser?.id === user.id ? 'bg-blue-50 border border-blue-400 dark:bg-blue-500/10 dark:border-blue-400/40' : 'hover:bg-slate-100 dark:hover:bg-slate-800'"
								>
									<img :src="user.avatar_url" class="w-9 h-9 rounded-full border border-white object-cover shadow" :alt="`Avatar ${user.name}`">
									<div class="min-w-0 flex-1">
										<div class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</div>
									</div>
									<span v-if="user.unread_messages_count" class="ml-2 rounded-full bg-red-500 px-2 py-1 text-[10px] font-bold text-white">{{ user.unread_messages_count }}</span>
								</Link>
							</template>
							<div v-else class="text-sm text-gray-500">Belum ada guru yang bisa dipilih.</div>
						</div>
					</div>
				</div>
				<div class="md:col-span-2 flex flex-col h-full">
					<div class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 shadow-card mb-2">
						<template v-if="selectedUser">
							<img :src="selectedUser.avatar_url" class="w-10 h-10 rounded-full border border-white object-cover shadow" :alt="`Avatar ${selectedUser.name}`">
							<div class="flex-1 min-w-0">
								<div class="font-bold text-base text-slate-900 dark:text-slate-100">{{ selectedUser.name }}</div>
								<div class="text-xs text-gray-500 dark:text-slate-400">{{ selectedUser.email }}</div>
							</div>
							<div class="flex items-center gap-2 ml-2">
								<button type="button" class="icon-button btn-info btn-xs" title="Detail Akun" @click="accountDetailOpen = true">
									<i class="fa-solid fa-circle-info"></i>
								</button>
								<form v-if="chats.length" method="POST" :action="route('superadmin.chat.destroyAll', { user: selectedUser.id })" onsubmit="return false;" id="delete-all-chats-form">
									<input type="hidden" name="_token" :value="page.props.csrf_token">
								<button
									type="submit"
									class="icon-button btn-danger btn-xs"
									data-confirm="Semua pesan dan gambar pada percakapan ini akan dihapus permanen. Lanjutkan?"
									data-confirm-title="Hapus Semua Pesan?"
									title="Hapus semua pesan"
								>
										<i class="fa-solid fa-trash"></i>
									</button>
								</form>
							</div>
						</template>
						<span v-else class="text-sm text-gray-500">Pilih guru untuk mulai chat.</span>
					</div>
					<div class="flex-1 overflow-y-auto p-2 md:p-4 rounded-2xl shadow-inner" style="background: white; background-image: linear-gradient(rgba(120,120,120,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(120,120,120,0.06) 1px, transparent 1px); background-size: 32px 32px;">
						<template v-if="selectedUser">
							<template v-if="chats.length > 0">
								<div v-for="chat in chats" :key="chat.id" class="mb-3 flex items-end gap-2" :class="isOwn(chat) ? 'justify-end' : 'justify-start'">
									<img v-if="!isOwn(chat)" :src="senderAvatarUrl(chat)" class="h-8 w-8 shrink-0 rounded-full border border-white object-cover shadow" :alt="`Avatar ${chat.from_user?.name ?? 'Pengirim'}`">
									<div class="max-w-[88%] sm:max-w-xl">
										<div
											class="rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm"
											:class="isOwn(chat) ? 'bg-blue-100 text-slate-800 dark:bg-blue-500/20 dark:text-slate-100' : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100'"
										>
											<div v-if="chat.message" class="whitespace-pre-wrap">{{ chat.message }}</div>
											<a v-if="chat.image_path" :href="route('superadmin.chat.image', chat.id)" target="_blank" class="mt-3 block">
												<img :src="route('superadmin.chat.image', chat.id)" class="max-h-56 rounded-2xl border border-slate-200 max-w-full object-cover dark:border-slate-700">
											</a>
										</div>
										<div class="flex items-center gap-2 mt-1 text-xs text-gray-400 dark:text-slate-500" :class="isOwn(chat) ? 'justify-end' : 'justify-start'">
											<span>{{ chat.created_at_formatted }}</span>
											<span v-if="isOwn(chat) && chat.is_read" class="text-green-500"><i class="fa-solid fa-check-double"></i></span>
											<span v-else-if="isOwn(chat)"><i class="fa-solid fa-check"></i></span>
										</div>
									</div>
									<img v-if="isOwn(chat)" :src="authAvatarUrl" class="h-8 w-8 shrink-0 rounded-full border border-white object-cover shadow" alt="Avatar Admin">
								</div>
							</template>
							<div v-else class="text-sm text-gray-500">Belum ada pesan untuk percakapan ini.</div>
						</template>
						<div v-else class="text-sm text-gray-500">Pilih guru untuk melihat percakapan.</div>
					</div>
					<div v-if="chatPaginator" class="mt-4">
						<PaginationLinks :paginator="chatPaginator" />
					</div>
					<div class="mt-4">
						<div class="card p-4">
							<form id="chat-send-form" enctype="multipart/form-data" class="space-y-2" @submit.prevent="sendMessage">
								<input type="hidden" name="to_user_id" :value="selectedUser ? selectedUser.id : ''">

								<div id="chat-image-preview" :class="imagePreviewUrl ? '' : 'hidden'">
									<div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-900/40">
										<img id="chat-image-preview-img" :src="imagePreviewUrl" alt="Preview gambar" class="h-16 w-16 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
										<div class="flex-1 min-w-0">
											<div id="chat-image-preview-meta" class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ imagePreviewMeta }}</div>
											<div class="text-[11px] text-slate-500 dark:text-slate-400">Preview lampiran, akan terkirim saat Anda klik kirim.</div>
										</div>
										<button type="button" class="icon-button btn-danger btn-xs" id="chat-image-preview-clear" title="Hapus gambar" @click="clearImagePreview">
											<i class="fa-solid fa-xmark"></i>
										</button>
									</div>
								</div>

								<div class="flex gap-2 items-end">
									<label class="flex items-center cursor-pointer mb-0">
										<input id="chat-image-input" type="file" name="image" accept="image/*" class="hidden" @change="onImageChange">
										<span class="icon-button" title="Lampirkan Media (maks 2 MB)"><i class="fa-solid fa-photo-film"></i></span>
									</label>
									<textarea v-model="messageForm.message" name="message" class="input flex-1 min-h-10" rows="1" placeholder="Tulis pesan..."></textarea>
									<button class="btn-primary px-5" type="submit" :disabled="messageForm.processing"><i class="fa-solid fa-paper-plane"></i></button>
								</div>
								<div class="text-[11px] text-slate-500 dark:text-slate-400">Lampiran gambar maksimal 2 MB.</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div
			v-if="selectedUser"
			id="modal-detail-akun"
			class="fixed inset-0 z-50 items-center justify-center bg-black/40"
			:class="accountDetailOpen ? 'flex' : 'hidden'"
			@click.self="accountDetailOpen = false"
		>
			<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-6 w-full max-w-md relative">
				<button class="absolute top-2 right-2 text-gray-400 hover:text-red-500" type="button" @click="accountDetailOpen = false">
					<i class="fa-solid fa-xmark fa-lg"></i>
				</button>
				<div class="flex flex-col items-center gap-3">
					<img :src="selectedUser.avatar_url" class="w-16 h-16 rounded-full border border-white object-cover shadow" :alt="`Avatar ${selectedUser.name}`">
					<div class="font-bold text-lg text-slate-900 dark:text-slate-100">{{ selectedUser.name }}</div>
					<div class="text-sm text-gray-500 dark:text-slate-400">{{ selectedUser.email }}</div>
					<div class="mt-2 w-full space-y-2">
						<div class="flex justify-between py-1 border-b">
							<span class="text-gray-500">Role</span>
							<span class="font-semibold text-slate-900 dark:text-slate-100">{{ selectedUser.role ?? '-' }}</span>
						</div>
						<div class="flex justify-between py-1 border-b">
							<span class="text-gray-500">Status Akun</span>
							<span class="font-semibold text-slate-900 dark:text-slate-100">{{ selectedUser.account_status ?? '-' }}</span>
						</div>
						<div class="flex justify-between py-1 border-b">
							<span class="text-gray-500">Access Token</span>
							<span class="font-mono text-xs text-slate-700 dark:text-slate-200">{{ selectedUser.access_token ?? '-' }}</span>
						</div>
						<div class="flex justify-between py-1 border-b">
							<span class="text-gray-500">Jenjang</span>
							<span class="text-slate-900 dark:text-slate-100">{{ selectedUser.jenjang ?? '-' }}</span>
						</div>
						<div class="flex justify-between py-1 border-b">
							<span class="text-gray-500">Satuan Pendidikan</span>
							<span class="text-slate-900 dark:text-slate-100">{{ selectedUser.satuan_pendidikan ?? '-' }}</span>
						</div>
						<div class="flex justify-between py-1 border-b">
							<span class="text-gray-500">No. WA</span>
							<span class="text-slate-900 dark:text-slate-100">{{ selectedUser.no_wa ?? '-' }}</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
