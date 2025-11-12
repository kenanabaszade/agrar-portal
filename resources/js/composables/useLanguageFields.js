import { reactive } from 'vue'

/**
 * Composable for managing multi-language form fields
 * Used with models that have HasTranslations trait (title, description as JSON)
 * 
 * @param {Array} languages - Array of language codes (e.g., ['az', 'en', 'ru'])
 * @param {Object} initialFields - Initial field values object with language keys
 * @returns {Object} - Reactive language fields and helper methods
 */
export function useLanguageFields(languages = ['az', 'en', 'ru'], initialFields = {}) {
  // Create reactive fields for each language
  const fields = reactive({})
  
  // Initialize fields for each language
  languages.forEach(lang => {
    fields[lang] = reactive({})
    
    // Initialize each field from initialFields
    Object.keys(initialFields).forEach(fieldName => {
      const fieldValue = initialFields[fieldName]
      
      if (typeof fieldValue === 'object' && fieldValue !== null) {
        // If it's already a language object, get the value for this language
        fields[lang][fieldName] = fieldValue[lang] || ''
      } else {
        // If it's a string or other value, use it for all languages or empty
        fields[lang][fieldName] = lang === 'az' ? (fieldValue || '') : ''
      }
    })
  })

  /**
   * Get field value for a specific language
   * @param {string} fieldName - Name of the field
   * @param {string} lang - Language code (default: 'az')
   * @returns {string} - Field value
   */
  const getField = (fieldName, lang = 'az') => {
    return fields[lang]?.[fieldName] || ''
  }

  /**
   * Set field value for a specific language
   * @param {string} fieldName - Name of the field
   * @param {string} value - Value to set
   * @param {string} lang - Language code (default: 'az')
   */
  const setField = (fieldName, value, lang = 'az') => {
    if (!fields[lang]) {
      fields[lang] = reactive({})
    }
    fields[lang][fieldName] = value
  }

  /**
   * Add a new field to all languages
   * @param {string} fieldName - Name of the field
   * @param {string} defaultValue - Default value (default: '')
   */
  const addField = (fieldName, defaultValue = '') => {
    languages.forEach(lang => {
      if (!fields[lang]) {
        fields[lang] = reactive({})
      }
      fields[lang][fieldName] = defaultValue
    })
  }

  /**
   * Get all field values as an object with language keys
   * This format matches the backend HasTranslations trait format
   * @param {string} fieldName - Name of the field
   * @returns {Object} - Object with language keys and values
   */
  const getFieldObject = (fieldName) => {
    const result = {}
    languages.forEach(lang => {
      result[lang] = getField(fieldName, lang)
    })
    return result
  }

  /**
   * Get all fields as an object ready to send to backend
   * Returns format: { title: { az: '...', en: '...' }, description: { az: '...', en: '...' } }
   * @param {Array} fieldNames - Array of field names to include (if empty, includes all)
   * @returns {Object} - Object ready for backend API
   */
  const getFieldsForBackend = (fieldNames = []) => {
    const result = {}
    
    // Get all field names from the first language
    const allFieldNames = fieldNames.length > 0 
      ? fieldNames 
      : Object.keys(fields[languages[0]] || {})
    
    allFieldNames.forEach(fieldName => {
      result[fieldName] = getFieldObject(fieldName)
    })
    
    return result
  }

  /**
   * Initialize fields from backend response
   * @param {Object} backendData - Data from backend (with language keys)
   */
  const initializeFromBackend = (backendData) => {
    Object.keys(backendData).forEach(fieldName => {
      const fieldValue = backendData[fieldName]
      
      if (typeof fieldValue === 'object' && fieldValue !== null) {
        // It's already a language object
        languages.forEach(lang => {
          setField(fieldName, fieldValue[lang] || '', lang)
        })
      }
    })
  }

  /**
   * Check if a field is empty for all languages
   * @param {string} fieldName - Name of the field
   * @returns {boolean} - True if field is empty in all languages
   */
  const isFieldEmpty = (fieldName) => {
    return languages.every(lang => !getField(fieldName, lang)?.trim())
  }

  /**
   * Clear all fields
   */
  const clearFields = () => {
    languages.forEach(lang => {
      Object.keys(fields[lang] || {}).forEach(fieldName => {
        fields[lang][fieldName] = ''
      })
    })
  }

  /**
   * Clear a specific field in all languages
   * @param {string} fieldName - Name of the field
   */
  const clearField = (fieldName) => {
    languages.forEach(lang => {
      setField(fieldName, '', lang)
    })
  }

  return {
    fields,
    languages,
    getField,
    setField,
    addField,
    getFieldObject,
    getFieldsForBackend,
    initializeFromBackend,
    isFieldEmpty,
    clearFields,
    clearField
  }
}
