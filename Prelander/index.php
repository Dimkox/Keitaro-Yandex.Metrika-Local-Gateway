<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    // ID вашего счетчика Яндекс.Метрики
    const ymCounterId = 12345678; // <-- ЗАМЕНИТЕ НА ВАШ ID СЧЕТЧИКА

    // Название параметра, в который будет записан ClientID
    const clientIdParam = 'sub_id_16';

    // Функция для добавления ClientID ко всем ссылкам
    function appendClientIdToLinks(clientId) {
        // Ищем все ссылки на странице. Для надежности добавьте вашим ссылкам
        // на кампанию Keitaro класс, например, class="keitaro-link"
        const links = document.querySelectorAll('a'); // или document.querySelectorAll('.keitaro-link')

        links.forEach(function(link) {
            try {
                const url = new URL(link.href);
                url.searchParams.set(clientIdParam, clientId);
                link.href = url.toString();
            } catch (e) {
                // Игнорируем невалидные URL (например, a href="#")
            }
        });
    }

    // Получаем ClientID от счетчика Метрики
    if (typeof ym === 'function') {
        ym(ymCounterId, 'getClientID', function(clientId) {
            if (clientId) {
                appendClientIdToLinks(clientId);
            }
        });
    }
});
</script>