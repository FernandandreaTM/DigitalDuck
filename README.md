# GENERADOR DE PRÁCTICOS - PATOLOGÍA UACH

**Sistema de Plantillas HTML para Prácticos Académicos**

[![Status](https://img.shields.io/badge/Status-En%20Desarrollo-orange)]()
[![License](https://img.shields.io/badge/License-Educational-green)]()

---

## 🎯 DESCRIPCIÓN

Generador web que permite a profesores crear prácticos académicos de Patología sin conocimientos de programación. Utiliza plantillas HTML responsivas y configuración JSON para generar presentaciones interactivas con slides navegables.

**Características:**
- 📝 Interfaz de formularios simple
- 🎨 Diseño responsivo móvil/desktop preservado
- 🔄 Sistema de navegación por slides
- 🔗 Enlaces externos parametrizables
- 📚 Bibliografía con capítulos/URLs variables
- 💾 Guardado de progreso en navegador

---

## 🚀 USO RÁPIDO

### Abrir el Generador
```bash
# Abrir en navegador
practico-generator.html
```

### Proceso de Creación
1. **Pestaña Info General:** Título, fecha, instructor, contacto
2. **Pestaña Contenidos:** Objetivos y bibliografía
3. **Pestaña Láminas:** Casos clínicos con preguntas
4. **Pestaña Generar:** Vista previa → Descargar HTML

---

## 📂 ESTRUCTURA

```
/
├── practico-generator.html    # Generador (interfaz)
├── template-base.html          # Plantilla HTML maestra
├── ejemplos/
│   └── practico-neoplasias.html  # Ejemplo funcional
└── README.md
```

---

## 📋 DATOS PARAMETRIZABLES

### Información General
- Título del práctico
- Fecha y horario
- Instructor y contacto
- Horario de atención

### Contenidos
- Objetivos de aprendizaje (array)
- Bibliografía (título, capítulo, URL)

### Láminas/Casos
- Título y concepto
- Información clínica (paciente, síntomas, exámenes)
- Enlaces (caso clínico, histología, referencias)
- Preguntas de análisis

---

## ⚙️ CARACTERÍSTICAS TÉCNICAS

**Frontend:**
- HTML5 + Tailwind CSS
- Vanilla JavaScript
- Lucide Icons
- LocalStorage para guardado

**Salida:**
- HTML standalone (sin dependencias externas)
- Responsivo móvil/desktop
- Navegación táctil + teclado
- Animaciones CSS preservadas

---

## 🎯 ESTADO ACTUAL

**En Desarrollo Activo**

- ✅ Interfaz de generador (4 pestañas)
- ✅ Validación de formularios
- ✅ Guardado/carga de progreso
- ✅ Vista previa JSON
- 🔄 **Generación HTML completa** (próxima fase)
- 🔄 Exportación a archivo

---

## 💾 FUNCIONES DISPONIBLES

### Guardar Progreso
```javascript
// Guarda en localStorage del navegador
saveProgress()
```

### Cargar Guardado
```javascript
// Recupera último guardado
loadProgress()
```

### Vista Previa
```javascript
// Muestra JSON configuración
updateJSONPreview()
```

---

## 📝 EJEMPLO JSON

```json
{
  "metadata": {
    "title": "Práctico VII: Neoplasias Malignas",
    "date": "Miércoles 01 de Octubre, 2025",
    "instructor": "TM. Fernanda López M., PhD"
  },
  "objectives": [
    "Identificar neoplasias malignas...",
    "Relacionar hallazgos microscópicos..."
  ],
  "cases": [
    {
      "title": "Lámina 1: Adenocarcinoma",
      "clinical": {...},
      "links": {...},
      "questions": [...]
    }
  ]
}
```

---

## 🛠️ PRÓXIMAS FASES

- [ ] Implementar generación HTML completa
- [ ] Sistema de descarga automática
- [ ] Validación exhaustiva de campos
- [ ] Plantillas predefinidas
- [ ] Exportación JSON para reutilización

---

## 👥 EQUIPO

**Desarrollador:** Fernanda López  
**Institución:** Universidad Austral de Chile (UACh)  
**Departamento:** Patología Básica ETMP148  
**Contacto:** fernanda.lopez@uach.cl

---

## 📄 LICENCIA

Uso Educacional - Universidad Austral de Chile

---

**Última Actualización:** Enero 2025  
**Versión:** 0.5 (Desarrollo)  
**Estado:** Funcional - Generación HTML pendiente
