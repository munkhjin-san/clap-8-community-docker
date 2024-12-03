import Autolinker from 'autolinker';
import moment from 'moment';

const mentionFormatter = (text: string | null, withUrl?: boolean) => {
    const cook = text ? text : ''
    const cooked = cook.replace(
        /<a href=\/app\/public\/user\?id=(\d+)>(.*?)<\/a>/g,
        '<span class="mntuser" data-userid="$1">$2</span>'
    ).replace(/\[To:([^\]]+):\]/g, (match, username) => {
        return `<span class="mntuser" data-username="${username}">@${username}</span>`;
    })
    return withUrl ? urlCheck(cooked) : cooked
}

const urlCheck = (text?: string | null) => {
    if (text) {
        var linkedText = Autolinker.link(text, { stripPrefix: false });
        return linkedText;
    }
}

const timeFormat = (time: number) => {
    const hours = Math.floor(time / 60);
    const minutes = time % 60;                
    return `${hours}時間${minutes}分`;
}

function debounce<T extends (...args: any[]) => any>(func: T, wait: number): (...args: Parameters<T>) => void {
    let timeout: ReturnType<typeof setTimeout>;
    return function(...args: Parameters<T>): void {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const detailedDateOptions = () => {
    const currentYear = moment().year();
    const options: any[] = [];

    for (let yearOffset = 2024; yearOffset <= currentYear + 1; yearOffset++) {
        const year = yearOffset;
        const firstHalfStart = moment(`${year}-03-01`);
        const firstHalfEnd = moment(`${year}-08-31`);
        const secondHalfStart = moment(`${year}-09-01`);
        const secondHalfEnd = moment(`${year}-02-28`);
        const evaluationFirst = moment(`${year}-02-01`);
        const evaluationSecond = moment(`${year}-08-01`);
        const firstHalf = {
            name: `${year}年上期（${firstHalfStart.format('YYYY.M.D')}～${firstHalfEnd.format('YYYY.M.D')}）`,
            value: firstHalfStart.format('YYYY-MM-DD'),
            evaluationDate: evaluationFirst.format('YYYY-MM-DD'),
            lastDate: secondHalfStart.clone().year(year - 1).format('YYYY-MM-DD'),
            lastname:  `${year - 1}年上期（${secondHalfStart.clone().year(year - 1).format('YYYY.M.D')}～${secondHalfEnd.year(year).format('YYYY.M.D')}）`
        };
        const secondHalf = {
            name: `${year}年下期（${secondHalfStart.format('YYYY.M.D')}～${secondHalfEnd.clone().year(year + 1).format('YYYY.M.D')}）`,
            value: secondHalfStart.format('YYYY-MM-DD'),
            evaluationDate: evaluationSecond.format('YYYY-MM-DD'),
            lastDate: firstHalfStart.format('YYYY-MM-DD'),
            lastname:  `${year}年上期（${firstHalfStart.format('YYYY.M.D')}～${firstHalfEnd.format('YYYY.M.D')}）` 
        };
        options.push(firstHalf);
        options.push(secondHalf);
    }
    return options;
}
const evaluationDateOptions = () => {
    let dateArray: string[] = []
    const currentYear = moment().year()

    for (let year = 2024; year <= currentYear + 1; year++) {
        dateArray.push(moment().set('year', year).set('month', 1).set('date', 1).format('YYYY-MM-DD'));
        dateArray.push(moment().set('year', year).set('month', 7).set('date', 1).format('YYYY-MM-DD'));
    }
    return dateArray
}
const generalPositions = () => {
    const positions = [
        { name: '一般職', value: 0 },
        { name: 'A', value: 10000 },
        { name: 'B', value: 30000 },
        { name: 'C', value: 50000 },
        { name: 'D', value: 70000 },
        { name: 'E', value: 100000 },
        { name: 'F', value: 150000 },
        { name: 'G', value: 200000 }
    ];
    return positions
}
const parseDate = (date: string | Date) => {
    return moment(date).format('YYYY年M月実施分')
}
export { 
    debounce, 
    mentionFormatter, 
    urlCheck, 
    timeFormat, 
    detailedDateOptions, 
    generalPositions,
    evaluationDateOptions,
    parseDate
}