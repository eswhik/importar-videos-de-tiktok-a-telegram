document.getElementById('openModal').addEventListener('click', () => {
    document.getElementById('apiModal').classList.remove('hidden');
});

document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('apiModal').classList.add('hidden');
});

window.onload = () => {
    const botToken = localStorage.getItem('botToken') || '';
    const channelId = localStorage.getItem('channelId') || '';
    const caption = localStorage.getItem('caption') || '';
    const includeNickname = localStorage.getItem('includeNickname') || 'yes';

    document.getElementById('bot_token').value = botToken;
    document.getElementById('channel_id').value = channelId;
    document.getElementById('caption').value = caption;
    document.getElementById('include_nickname').value = includeNickname;

    if (localStorage.getItem('botTokenBlurred') === 'true') {
        document.getElementById('bot_token').classList.add('blurred');
    }
};

document.getElementById('config_form').addEventListener('submit', async (e) => {
    e.preventDefault();

    localStorage.setItem('botToken', document.getElementById('bot_token').value.trim());
    localStorage.setItem('channelId', document.getElementById('channel_id').value.trim());
    localStorage.setItem('caption', document.getElementById('caption').value.trim());
    localStorage.setItem('includeNickname', document.getElementById('include_nickname').value);

    document.getElementById('bot_token').classList.add('blurred');
    localStorage.setItem('botTokenBlurred', 'true');

    document.getElementById('apiModal').classList.add('hidden');
});

document.getElementById('tiktok_form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const tiktokUrls = document.getElementById('tiktok_urls').value.trim().split('\n').map(url => url.trim());
    const botToken = localStorage.getItem('botToken') || '';
    const channelId = localStorage.getItem('channelId') || '';
    const caption = localStorage.getItem('caption') || '';
    const includeNickname = localStorage.getItem('includeNickname') || '';

    const sendRequests = async (urls, index) => {
        if (index >= urls.length) return;

        const formData = new FormData();
        formData.append('tiktok_urls', JSON.stringify([urls[index]]));
        formData.append('bot_token', botToken);
        formData.append('channel_id', channelId);
        formData.append('caption', caption);
        formData.append('include_nickname', includeNickname);

        const progressBarContainer = document.createElement('div');
        progressBarContainer.classList.add('relative', 'mb-4', 'bg-gray-200', 'rounded-full', 'h-2', 'w-full', 'z-30');
        progressBarContainer.innerHTML = `<div class="absolute top-0 left-0 bg-blue-600 h-full rounded-full" style="width: 0%;"></div>`;
        document.getElementById('response_message').appendChild(progressBarContainer);

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            const progress = progressBarContainer.querySelector('div');
            progress.style.width = '100%';

            setTimeout(() => {
                const messageHTML = `
                    <div class="bg-green-100 text-green-800 p-4 mb-4 rounded">
                        <p>${data[0].message}</p>
                    </div>
                `;
                document.getElementById('response_message').insertAdjacentHTML('beforeend', messageHTML);
                sendRequests(urls, index + 1);
            }, 1000);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    sendRequests(tiktokUrls, 0);
});
