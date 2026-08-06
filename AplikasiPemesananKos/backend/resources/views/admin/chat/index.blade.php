@extends('admin.layout')

@section('content')
<div class="flex h-[calc(100vh-130px)] bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    
    <!-- LEFT SIDE: DAFTAR KONTAK -->
    <div class="w-full md:w-1/3 border-r border-slate-200 flex flex-col bg-slate-50">
        
        <!-- Header Pencarian -->
        <div class="p-4 bg-white border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-800 mb-3">Pesan Masuk</h2>
            <div class="relative">
                <input type="text" id="searchContact" placeholder="Cari user..." class="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-cyan-500 transition-all outline-none">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
            </div>
        </div>

        <!-- List Kontak (Scrollable) -->
        <div class="flex-1 overflow-y-auto custom-scrollbar" id="contactList">
            <!-- Loading State -->
            <div class="flex flex-col items-center justify-center h-40 text-slate-400">
                <i data-lucide="loader-2" class="w-6 h-6 animate-spin mb-2"></i>
                <span class="text-xs">Memuat percakapan...</span>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: ISI CHAT -->
    <div class="hidden md:flex w-2/3 flex-col bg-[#eef2f6] relative" id="chatContainer">
        
        <!-- Chat Header -->
        <div class="h-16 bg-white border-b border-slate-200 flex items-center px-6 justify-between shadow-sm z-10 hidden" id="chatHeader">
            <div class="flex items-center">
                <img src="" id="headerAvatar" class="w-10 h-10 rounded-full bg-slate-200 object-cover border border-slate-100">
                <div class="ml-3">
                    <h3 class="font-bold text-slate-800 text-sm" id="headerName">Nama User</h3>
                    <p class="text-xs text-green-600 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Online
                    </p>
                </div>
            </div>
            <button class="p-2 hover:bg-slate-100 rounded-full text-slate-400 transition">
                <i data-lucide="more-vertical" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Chat Area (Messages) -->
        <div class="flex-1 overflow-y-auto p-6 space-y-3 custom-scrollbar" id="messageArea">
            <!-- Default Placeholder (Saat belum pilih chat) -->
            <div class="flex flex-col items-center justify-center h-full text-slate-400" id="noChatSelected">
                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm">
                    <i data-lucide="message-square" class="w-10 h-10 text-cyan-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-600">Admin Chat Center</h3>
                <p class="text-sm mt-1 max-w-xs text-center">Pilih salah satu kontak di sebelah kiri untuk mulai membalas pesan.</p>
            </div>
        </div>

        <!-- Input Area -->
        <div class="bg-white p-4 hidden border-t border-slate-200" id="inputArea">
            <form id="chatForm" class="flex items-center gap-3">
                <input type="hidden" id="receiverId">
                
                <button type="button" class="p-2 text-slate-400 hover:text-cyan-600 hover:bg-slate-100 rounded-full transition">
                    <i data-lucide="paperclip" class="w-5 h-5"></i>
                </button>
                
                <input type="text" id="messageInput" autocomplete="off" placeholder="Ketik balasan..." 
                    class="flex-1 py-3 px-5 bg-slate-100 border-none rounded-full text-sm focus:ring-2 focus:ring-cyan-500 outline-none transition-all">
                
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white p-3 rounded-full shadow-lg shadow-cyan-200 transition-transform transform active:scale-95 flex items-center justify-center">
                    <i data-lucide="send" class="w-5 h-5 ml-0.5"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- JAVASCRIPT LOGIC --}}
<script>
    const adminId = {{ Auth::id() }}; 
    let activeChatId = null;
    let refreshInterval = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadContacts();
        // Auto refresh kontak tiap 10 detik (Cek pesan baru)
        setInterval(loadContacts, 10000); 
    });

    // 1. FETCH CONTACTS
    function loadContacts() {
        fetch("{{ route('admin.chat.contacts') }}")
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('contactList');
                let html = '';

                if(data.length === 0) {
                    html = `
                        <div class="flex flex-col items-center justify-center h-64 text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 mb-2 opacity-50"></i>
                            <span class="text-xs">Belum ada pesan masuk</span>
                        </div>`;
                }

                data.forEach(user => {
                    // Highlight chat yang sedang dibuka
                    const isActive = (activeChatId == user.id);
                    const bgClass = isActive ? 'bg-cyan-50 border-r-4 border-cyan-500' : 'hover:bg-slate-100 border-transparent';
                    
                    // Badge Unread
                    const unreadHtml = user.unread > 0 
                        ? `<div class="bg-red-500 text-white text-[10px] font-bold h-5 min-w-[20px] px-1.5 flex items-center justify-center rounded-full shadow-sm">${user.unread}</div>` 
                        : '';
                    
                    const timeClass = user.unread > 0 ? 'text-green-600 font-bold' : 'text-slate-400';

                    html += `
                        <div onclick="openChat(${user.id}, '${user.name}', '${user.avatar}')" 
                             class="group flex items-center p-4 border-b border-slate-100 cursor-pointer transition-all duration-200 ${bgClass}">
                            <div class="relative">
                                <img src="${user.avatar}" class="w-12 h-12 rounded-full object-cover border border-slate-200 group-hover:scale-105 transition-transform">
                                ${user.unread > 0 ? '<span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>' : ''}
                            </div>
                            <div class="ml-4 flex-1 overflow-hidden">
                                <div class="flex justify-between items-center mb-1">
                                    <h4 class="text-sm font-bold text-slate-800 truncate">${user.name}</h4>
                                    <span class="text-[10px] ${timeClass}">${user.time}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <p class="text-xs text-slate-500 truncate w-36 ${user.unread > 0 ? 'font-semibold text-slate-700' : ''}">${user.last_message}</p>
                                    ${unreadHtml}
                                </div>
                            </div>
                        </div>
                    `;
                });
                list.innerHTML = html;
                lucide.createIcons(); // Refresh icons jika ada yang baru
            });
    }

    // 2. OPEN CHAT ROOM
    window.openChat = function(userId, name, avatar) {
        activeChatId = userId;
        document.getElementById('receiverId').value = userId;
        
        // Update Header Info
        document.getElementById('headerName').innerText = name;
        document.getElementById('headerAvatar').src = avatar;
        
        // Show UI Components
        document.getElementById('noChatSelected').classList.add('hidden');
        document.getElementById('chatHeader').classList.remove('hidden');
        document.getElementById('chatHeader').classList.add('flex');
        document.getElementById('inputArea').classList.remove('hidden');

        // Scroll to active chat visual
        loadContacts(); 

        // Load Messages Immediately
        loadMessages();

        // Start Polling (Refresh chat every 3 seconds)
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(loadMessages, 3000);
    };

    // 3. FETCH MESSAGES
    function loadMessages() {
        if (!activeChatId) return;

        fetch(`/admin/chat/conversation/${activeChatId}`)
            .then(res => res.json())
            .then(data => {
                const area = document.getElementById('messageArea');
                let html = '';

                data.forEach(msg => {
                    const isMe = msg.sender_id == adminId;
                    
                    // Bubble Styling
                    const align = isMe ? 'justify-end' : 'justify-start';
                    // Warna bubble: Admin (Cyan Muda), User (Putih)
                    const bubbleColor = isMe ? 'bg-cyan-100 text-slate-800' : 'bg-white text-slate-800 border border-slate-100'; 
                    const rounded = isMe ? 'rounded-l-2xl rounded-tr-2xl rounded-br-sm' : 'rounded-r-2xl rounded-tl-2xl rounded-bl-sm';
                    
                    const date = new Date(msg.created_at);
                    const time = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                    html += `
                        <div class="flex ${align} mb-2 fade-in">
                            <div class="max-w-[75%] ${bubbleColor} px-4 py-2 ${rounded} shadow-sm relative group">
                                <p class="text-sm leading-relaxed">${msg.message}</p>
                                <div class="flex items-center justify-end gap-1 mt-1 opacity-70">
                                    <span class="text-[10px] font-medium">${time}</span>
                                    ${isMe ? `<i data-lucide="check${msg.is_read ? '-check' : ''}" class="w-3 h-3 ${msg.is_read ? 'text-cyan-600' : 'text-slate-400'}"></i>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                // Cek apakah user sedang scroll ke atas (history) atau di bawah
                // Jika di bawah, auto scroll. Jika baca history, jangan scroll.
                const isScrolledToBottom = area.scrollHeight - area.clientHeight <= area.scrollTop + 100;
                
                area.innerHTML = html;
                lucide.createIcons();

                if (isScrolledToBottom || html === '') { // Scroll ke bawah pas pertama load
                    area.scrollTop = area.scrollHeight;
                }
            });
    }

    // 4. SEND MESSAGE
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const message = input.value;
        const receiverId = document.getElementById('receiverId').value;

        if (!message.trim()) return;

        // Kosongkan input agar terasa responsif
        input.value = '';

        // POST Request
        fetch("{{ route('admin.chat.send') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                receiver_id: receiverId,
                message: message
            })
        })
        .then(res => res.json())
        .then(data => {
            loadMessages(); // Refresh chat langsung
            loadContacts(); // Refresh list kontak (update last msg)
            
            // Force scroll down
            const area = document.getElementById('messageArea');
            setTimeout(() => { area.scrollTop = area.scrollHeight; }, 100);
        });
    });
</script>

<style>
    /* Styling Scrollbar Khusus Chat */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection