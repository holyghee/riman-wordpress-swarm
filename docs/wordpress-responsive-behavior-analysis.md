# WordPress Container und Wide Responsive Verhalten - Analyse

## Zusammenfassung der Ergebnisse

### WordPress Twenty Twenty-Five Theme - Standard Responsive Verhalten

WordPress verwendet ein sehr einfaches und elegantes System für responsive Container und Wide Alignment:

## 🎯 Kernfindungen

### 1. WordPress Standard CSS Variablen
```css
:root {
  --wp--style--global--content-size: 1140px;  /* Standard Container */
  --wp--style--global--wide-size: 1170px;     /* Wide Alignment - nur 30px breiter! */
  --wp--preset--spacing--50: clamp(30px, 5vw, 50px);  /* Responsive Padding */
}
```

### 2. Responsive Padding System
WordPress verwendet `clamp(30px, 5vw, 50px)` für responsive Padding:
- **Desktop (1920px):** 50px Padding (maximaler Wert)
- **Laptop (1440px):** 50px Padding (maximum erreicht)
- **Tablet (768px):** 38.4px Padding (5vw von 768px)
- **Mobile (375px):** 30px Padding (minimaler Wert)

### 3. Viewport Breakpoint Verhalten

| Viewport | Content (1140px) | Wide (1170px) | Effektive Breite | Verhalten |
|----------|------------------|---------------|------------------|-----------|
| **Desktop (1920px)** | 1140px | 1170px | Unterschiedlich | ✅ 30px Unterschied sichtbar |
| **Laptop (1440px)** | 1140px | 1170px | Unterschiedlich | ✅ 30px Unterschied sichtbar |
| **Tablet (768px)** | 691px | 691px | **Identisch** | ⚠️ Durch Viewport limitiert |
| **Mobile (375px)** | 315px | 315px | **Identisch** | ⚠️ Durch Viewport limitiert |

## 🚨 Wichtige Erkenntnisse

### Container vs Wide - Der minimale Unterschied
WordPress macht nur einen **30px Unterschied** zwischen Container (1140px) und Wide (1170px). Dieser kleine Unterschied ist:
- **Sichtbar** auf großen Bildschirmen (>1200px verfügbare Breite)
- **NICHT sichtbar** auf Tablet/Mobile (viewport-limitiert)

### Responsive Verhalten
```css
/* WordPress Responsive Logik */
.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
  max-width: var(--wp--style--global--content-size);  /* 1140px */
  margin-left: auto !important;
  margin-right: auto !important;
}

.is-layout-constrained > .alignwide {
  max-width: var(--wp--style--global--wide-size);  /* 1170px */
}
```

**Effekt:** Sobald der Viewport kleiner als die maximale Breite + Padding wird, sind beide identisch.

## 📱 Service Cards - Aktueller Stand vs WordPress Standard

### Service Cards Responsive Implementierung

Die Service Cards verwenden ein **komplexeres System** als WordPress Standard:

```css
/* Service Cards - 3 verschiedene responsive Modi */
.riman-service-cards-wrap.sc-container      /* Standard Container */
.riman-service-cards-wrap.alignwide         /* WordPress Wide */
.riman-service-cards-wrap.sc-responsive     /* Custom Responsive */
```

### Service Cards vs WordPress Comparison

| Eigenschaft | WordPress Standard | Service Cards Aktuell |
|-------------|-------------------|----------------------|
| **Container Width** | 1140px | 1140px ✅ |
| **Wide Width** | 1170px | 1440px ❌ |
| **Responsive Padding** | clamp(30px, 5vw, 50px) | Eigenes System ❌ |
| **Tablet Verhalten** | Gleiche Breite | 2-spaltig ✅ |
| **Mobile Verhalten** | Gleiche Breite | 1-spaltig ✅ |

## 🎯 Empfehlungen für Service Cards Konsistenz

### 1. WordPress CSS Variablen verwenden
```css
.riman-service-cards-wrap {
  /* WordPress Standard Werte verwenden */
  --local-content-size: var(--wp--style--global--content-size, 1140px);
  --local-wide-size: var(--wp--style--global--wide-size, 1170px);  /* 1170px statt 1440px */
}
```

### 2. WordPress Padding System übernehmen
```css
/* WordPress-konsistentes Padding */
.riman-service-cards-wrap {
  padding-left: var(--wp--style--root--padding-left);
  padding-right: var(--wp--style--root--padding-right);
}
```

### 3. WordPress Responsive Breakpoints befolgen

**WordPress Standard Breakpoints:**
- **Desktop:** >1200px (Container und Wide unterschiedlich)
- **Tablet/Mobile:** <1200px (Container und Wide identisch)

**Service Cards sollten:**
```css
/* Desktop: WordPress Standard befolgen */
@media (min-width: 1201px) {
  .riman-service-cards-wrap.alignwide {
    max-width: var(--wp--style--global--wide-size);  /* 1170px */
  }
}

/* Tablet/Mobile: Identisches Verhalten */
@media (max-width: 1200px) {
  .riman-service-cards-wrap.alignwide,
  .riman-service-cards-wrap:not(.alignfull) {
    max-width: calc(100vw - 2 * var(--wp--style--root--padding-left));
  }
}
```

## ✅ Fazit

WordPress verwendet ein **elegantes, minimalistisches** responsive System:
- Nur 30px Unterschied zwischen Container und Wide
- Viewport-abhängiges Verhalten (identisch bei kleinen Bildschirmen)
- Konsistentes Padding-System mit `clamp()`

**Service Cards sollten diesem WordPress Standard folgen** für:
- ✅ Konsistente Benutzererfahrung
- ✅ Vorhersagbares Verhalten
- ✅ Theme-Kompatibilität
- ✅ Wartbarkeit

Das aktuelle Service Cards System ist **funktionaler aber komplexer** als nötig. WordPress' Ansatz ist **simpler und standardkonformer**.