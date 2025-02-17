type ValidationRule = 'required' | 'max' | 'min' | 'halfspace' | 'email' | 'number';

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
    case 'email':
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return {
        isValid: emailRegex.test(value),
        errorMessage: '有効なメールアドレスを入力してください。'
      };
    case 'number':
      const numberRegex = /[a-zA-Z\uFF21-\uFF3A\uFF41-\uFF5A]/
      return {
        isValid: !numberRegex.test(value),
        errorMessage: '有効な番号を入力してください。'
      }
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