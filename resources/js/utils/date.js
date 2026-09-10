const LIMA_TIME_ZONE = 'America/Lima';

export function todayInLima() {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: LIMA_TIME_ZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date());

    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

    return `${values.year}-${values.month}-${values.day}`;
}

export function daysBetweenDates(from, to) {
    const fromDate = new Date(`${String(from).slice(0, 10)}T00:00:00`);
    const toDate = new Date(`${String(to).slice(0, 10)}T00:00:00`);

    return (toDate - fromDate) / 86400000;
}
