import Autolinker from 'autolinker';
import { DateTime } from 'luxon'
import { customRef } from 'vue'
import { filesize } from 'filesize';
function useDebouncedRef(value:any, delay = 200) {
    let timeout:ReturnType<typeof setTimeout> | number = 300;
    return customRef((track, trigger) => {
      return {
        get() {
          track()
          return value
        },
        set(newValue) {
          clearTimeout(timeout)
          timeout = setTimeout(() => {
            value = newValue
            trigger()
          }, delay)
        }
      }
    })
  }
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


    const irreguarOptions = [
        { name: '2024年上期（2024.3.1～2024.8.31）', year: '2024', which_half: 'first', short_name: '2024年上期' },
        { name: '2024年下期（2024.9.1～2025.2.28）', year: '2024', which_half: 'second', short_name: '2024年下期' },
        { name: '2025年上期（2025.3.1～2025.9.30）', year: '2025', which_half: 'first', short_name: '2025年上期'},
        { name: '2025年下期（2025.10.1～2026.3.31）', year: '2025', which_half: 'second', short_name: '2025年下期'},
        { name: '2026年上期（2026.4.1～2026.9.30）', year: '2026', which_half: 'first', short_name: '2026年上期'},
        { name: '2026年下期（2026.10.1～2027.3.31）', year: '2026', which_half: 'second', short_name: '2026年下期' },
    ].reverse()
    return irreguarOptions;
}
const evaluationDateOptions = () => {
    let dateArray: string[] = [
        '2024-02-01',
        '2024-08-01',
        '2025-03-01',
        '2025-10-01',
        '2026-04-01',
        '2026-10-01',
    ]
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

const taskStatusBackgrounds = ['black', '#eb7a00', 'green']
const DateParser = (date:string) => {
    const instance = customParser(date)
    const today = DateTime.now()
    const format = instance.hasSame(today, 'day') ? 'T' : instance.hasSame(today, 'year') ? 'M / d (ccc) HH:mm' : 'y / M / d (ccc) HH:mm'      
    return instance.toFormat(format)  
}
const decidedAnswers = [
    '完全に理解し、実務で活用できる自信がある',
    '十分に理解できているが、実務での応用に不安がある', 
    'ほとんど理解できていない'
]



const DATE_FORMATS = [
  "yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'", // ISO 8601 with microseconds
  "yyyy-MM-dd HH:mm:ss",             // SQL-like format
];

function customParser(dateStr: string, zone: string = "Asia/Tokyo"): DateTime {
  for (const format of DATE_FORMATS) {
    const dt = DateTime.fromFormat(dateStr, format, { zone });
    if (dt.isValid) return dt;
  }

  // Fallback to ISO parsing if it wasn't matched in formats
  const dtISO = DateTime.fromISO(dateStr, { zone });
  if (dtISO.isValid) return dtISO;

  throw new Error(`Invalid date format: ${dateStr}`);
}

const fileSizeParser = (bytes: number) => {
    if(bytes > 1000000) return filesize(bytes, {standard: "jedec", round: 1});
    else return filesize(bytes, {standard: "jedec", round: 0});
}
const kintoneFileUrlBuilder = (file:any) => {
    const params = new URLSearchParams();
    params.set('key', file.fileKey);
    params.set('access', 'full');
    params.set('name', file.name);
    return `/kintone_file?${params.toString()}`
}

const amountOfMoneyParser = (amount: number) => {
    if (Number.isNaN(amount)) return '-';
    return new Intl.NumberFormat("ja-JP").format(amount);
}
const truncatedName = (filename: string, maxLength: number) => {
  if (!filename) return ''

  // split extension
  const lastDot = filename.lastIndexOf('.')
  if (lastDot === -1) {
    // no extension, just truncate raw
    return filename.length > maxLength
      ? filename.slice(0, maxLength - 3) + '...'
      : filename
  }

  const name = filename.slice(0, lastDot)
  const ext = filename.slice(lastDot)

  if (filename.length <= maxLength) return filename

  // leave room for extension + ellipsis
  const keep = maxLength - ext.length - 3
  return name.slice(0, keep) + '...' + ext
}
const valueTypeOptions = [
  { name: '件', value: 'amount' },
  { name: '率', value: 'rate' },
  { name: '金', value: 'currency'},
]

const lineOptions = [
  { name: '売上', value: 'sales' },
  { name: '販管費', value: 'expense' },
  { name: '利益', value: 'profit' },
  { name: '利益率', value: 'profit_rate' }
]

const kindOptions = [
  { name: '入力', value: 'input' },
  { name: '計算', value: 'derived' }
]
const TODAY = DateTime.now().toISODate();
const key = (id: number | null) => `weather:${id}:lastDone`;
const markTodayDone = (userId: number | null) => {
  localStorage.setItem(key(userId), TODAY);
} 
const isTodayDone = (userId: number | null) => {
  return localStorage.getItem(key(userId)) === TODAY
}
export { 
    debounce, 
    mentionFormatter, 
    urlCheck, 
    timeFormat, 
    detailedDateOptions, 
    generalPositions,
    evaluationDateOptions,
    taskStatusBackgrounds,
    DateParser,
    decidedAnswers,
    useDebouncedRef,
    customParser,
    fileSizeParser,
    kintoneFileUrlBuilder,
    amountOfMoneyParser,
    truncatedName,
    valueTypeOptions,
    lineOptions,
    kindOptions,
    markTodayDone, 
    isTodayDone
}