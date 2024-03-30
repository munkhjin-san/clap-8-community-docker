type ValidationRule = 'required' | 'max' | 'min' | 'halfspace';

interface ValidationResult {
  isValid: boolean;
  errorMessage?: string;
}

function validateRule(rule: ValidationRule, value: any | any[], params?: number): ValidationResult {
  switch (rule) {
    case 'required':
      return {
        isValid: (typeof value === 'string' && value.length > 0) || (Array.isArray(value) && value.length > 0) || (typeof value === 'number' && value !== null && value !== undefined),
        errorMessage: '必須です',
      };
    case 'max':
      return {
        isValid: value.length <= params!,
        errorMessage: `${params}文字以内で入力してください。`,
      };
    case 'min':
      return {
        isValid: value.length >= params!,
        errorMessage: `${params}文字以内で入力してください。`,
      };
    case 'halfspace':
      return {
        isValid: !/[\u3000]/.test(value),
        errorMessage: `半角スペースのみ入力してください。`,
      };
    default:
      return { isValid: true };
  }
}

export async function validator(rules: string, data: any | any[]): Promise<ValidationResult> {
  const ruleArray = rules.split('|');
  for (const ruleItem of ruleArray) {
    const [rule, params] = ruleItem.split(':');
    const validationResult = validateRule(rule as ValidationRule, data, parseInt(params));
    if (!validationResult.isValid) {
      return validationResult;
    }
  }

  return { isValid: true };
}