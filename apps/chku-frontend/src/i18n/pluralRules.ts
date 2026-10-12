// Russian plural forms for "a | b | c" messages: 1 оценка | 2 оценки | 5 оценок.
export function russianPlural(choice: number, choicesLength: number): number {
  if (choicesLength < 3) return choice === 1 ? 0 : 1

  const n = Math.abs(choice) % 100
  const last = n % 10
  if (n > 10 && n < 20) return 2
  if (last === 1) return 0
  if (last >= 2 && last <= 4) return 1
  return 2
}

export const pluralRules = { ru: russianPlural }
