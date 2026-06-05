document.addEventListener('DOMContentLoaded', function() {

    const filterBy = document.getElementById('filter_by');
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    let isAutoFilling = false;

    /* ---------- Helpers ---------- */
    const formatDate = (date) => {
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        return `${d}-${m}-${y}`;
    };

    const parseDate = (str) => {
        const [d, m, y] = str.split('-').map(Number);
        return new Date(y, m - 1, d);
    };

    const startOfWeek = (date) => {
        const d = new Date(date);
        const day = d.getDay() || 7;
        if (day !== 1) d.setDate(d.getDate() - (day - 1));
        return d;
    };

    const endOfWeek = (date) => {
        const d = startOfWeek(date);
        d.setDate(d.getDate() + 6);
        return d;
    };

    const startOfQuarter = (date) => {
        const q = Math.floor(date.getMonth() / 3);
        return new Date(date.getFullYear(), q * 3, 1);
    };

    const endOfQuarter = (date) => {
        const start = startOfQuarter(date);
        return new Date(start.getFullYear(), start.getMonth() + 3, 0);
    };

    /* ---------- Core Filler ---------- */
    const fillDates = (type) => {

        isAutoFilling = true;

        const today = new Date();
        let from, to;

        switch (type) {

            case 'today':
                from = to = today;
                break;

            case 'yesterday':
                from = to = new Date(today.setDate(today.getDate() - 1));
                break;

            case 'this_week':
                from = startOfWeek(new Date());
                to = endOfWeek(new Date());
                break;

            case 'previous_week':
                const lastWeek = new Date();
                lastWeek.setDate(lastWeek.getDate() - 7);
                from = startOfWeek(lastWeek);
                to = endOfWeek(lastWeek);
                break;

            case 'this_month':
                from = new Date(today.getFullYear(), today.getMonth(), 1);
                to = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;

            case 'previous_month':
                from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                to = new Date(today.getFullYear(), today.getMonth(), 0);
                break;

            case 'this_quarter':
                from = startOfQuarter(today);
                to = endOfQuarter(today);
                break;

            case 'previous_quarter':
                const prevQuarter = new Date(today);
                prevQuarter.setMonth(prevQuarter.getMonth() - 3);
                from = startOfQuarter(prevQuarter);
                to = endOfQuarter(prevQuarter);
                break;

            case 'this_year':
                from = new Date(today.getFullYear(), 0, 1);
                to = new Date(today.getFullYear(), 11, 31);
                break;

            case 'previous_year':
                from = new Date(today.getFullYear() - 1, 0, 1);
                to = new Date(today.getFullYear() - 1, 11, 31);
                break;

            case 'custom':
                isAutoFilling = false;
                return;
        }

        dateFrom.value = formatDate(from);
        dateTo.value = formatDate(to);

        isAutoFilling = false;
    };

    /* ---------- Events ---------- */
    filterBy.addEventListener('change', function() {
        fillDates(this.value);
    });

    const handleManualChange = () => {
        if (isAutoFilling) return;

        filterBy.value = 'custom';

        if (dateFrom.value && dateTo.value) {
            const from = parseDate(dateFrom.value);
            const to = parseDate(dateTo.value);

            if (from > to) {
                dateTo.value = dateFrom.value;
            }
        }
    };

    dateFrom.addEventListener('change', handleManualChange);
    dateTo.addEventListener('change', handleManualChange);

    /* ---------- DEFAULT ON LOAD ---------- */
    // filterBy.value = 'this_month';
    // fillDates('this_month');

});