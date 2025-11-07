export type RecordKind = 'PLAN' | 'PIPELINE' | 'ACTUAL';
export type Stage = 'WON' | 'A' | 'B' | 'C' | 'D' | 'E' | 'LOST';
export type DeliveryStatus = 'COMPLETED' | 'ORDERED_NOT_COMPLETED';

export const KIND_TAB_OPTIONS: { kind: RecordKind; label: string }[] = [
  { kind: 'PLAN', label: '目標値' },
  { kind: 'PIPELINE', label: '見込み' },
  { kind: 'ACTUAL', label: '実績' },
];

export const STAGE_LABEL: Record<Stage, string> = {
  WON: '①受注済',
  A: '②確度A',
  B: '③確度B',
  C: '④確度C',
  D: '⑤確度D',
  E: '⑥確度E',
  LOST: '失注',
};

export const STAGE_PIPELINE_LIST: Stage[] = ['A', 'B', 'C', 'D', 'E'];

export const STAGE_WEIGHT: Record<Stage, number> = {
  WON: 1.0,
  A: 0.9,
  B: 0.7,
  C: 0.5,
  D: 0.3,
  E: 0.1,
  LOST: 0,
};

export const DELIVERY_LABEL: Record<DeliveryStatus, string> = {
  COMPLETED: '★竣工済',
  ORDERED_NOT_COMPLETED: '①受注済未竣工',
};

