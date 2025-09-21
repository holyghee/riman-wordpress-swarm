# Video-Poster Funktionalität Testen

## 1. Browser DevTools - Console Tests

### Schneller Test (kopiere in Console):
```javascript
// Grundcheck - sind Videos mit Poster da?
console.log('Cover mit Videos:', document.querySelectorAll('.riman-cover--has-poster').length);
console.log('Lazy Videos:', document.querySelectorAll('video[data-riman-cover-lazy]').length);

// Poster Status prüfen
document.querySelectorAll('video[data-riman-cover-lazy]').forEach((v, i) => {
  console.log(`Video ${i+1}: Preload=${v.preload}, Poster=${!!v.poster}, Opacity=${getComputedStyle(v).opacity}`);
});
```

### Ausführlicher Test:
Komplett Script aus `docs/video-poster-debug-console.js` kopieren und in Console einfügen.

## 2. Network Tab Prüfen

### Was zu erwarten ist:
- **Sofort**: Poster-Bilder (.png/.jpg) werden geladen
- **Später/Bei Bedarf**: Video-Dateien (.mp4/.webm) werden geladen
- **Nicht sofort**: Videos sollten NICHT beim Seitenladen starten

### Schritte:
1. DevTools öffnen (F12)
2. Network Tab → Filter auf "Media" oder "All"
3. Seite neu laden
4. Beobachten:
   - Poster-Bilder laden sofort
   - Videos laden erst bei Intersection/Hover

## 3. Performance Tab Prüfen

### LCP (Largest Contentful Paint) messen:
1. DevTools → Performance Tab
2. Reload and record
3. Im Timeline schauen:
   - LCP sollte Poster-Bild sein (nicht Video)
   - Video loading sollte später/asynchron sein

### Lighthouse Test:
1. DevTools → Lighthouse Tab
2. Performance Test laufen lassen
3. LCP Score prüfen (sollte besser sein)

## 4. Elements Tab Prüfen

### HTML Struktur erwarten:
```html
<div class="wp-block-cover riman-cover--has-poster"
     data-riman-poster="poster-url.jpg"
     style="background-image: url('poster-url.jpg');">
  <video data-riman-cover-lazy="1"
         poster="poster-url.jpg"
         preload="none"
         data-src="video.mp4"
         class="riman-cover-video">
    <!-- Video sources -->
  </video>
</div>
```

### CSS Styles prüfen:
```css
/* Video sollte zunächst unsichtbar sein */
.riman-cover--has-poster video {
    opacity: 0;
}

/* Poster als Background */
.riman-cover--has-poster {
    background-image: url('poster.jpg');
    background-size: cover;
}
```

## 5. Block Editor Prüfen

### Im WordPress Backend:
1. Cover Block mit Video erstellen
2. Rechts in den Block Settings schauen
3. "Posterbild" Panel sollte sichtbar sein
4. Poster-Bild auswählen können

### Attribute prüfen (Console im Editor):
```javascript
// Im Block Editor Console
wp.data.select('core/block-editor').getSelectedBlock().attributes.rimanPosterId
wp.data.select('core/block-editor').getSelectedBlock().attributes.rimanPosterUrl
```

## 6. Manuelle Sichtprüfung

### Was zu sehen sein sollte:
1. **Beim Laden**: Poster-Bild sofort sichtbar
2. **Bei Scroll**: Video startet smooth über Poster
3. **Performance**: Keine Ruckler beim Laden

### Was NICHT passieren sollte:
- Videos laden sofort beim Seitenladen
- Leere/weiße Bereiche vor Poster-Anzeige
- Abrupte Übergänge zwischen Poster und Video

## 7. Error Debugging

### Häufige Probleme:
```javascript
// Poster-Editor nicht geladen?
console.log('Poster Editor:', !!window.wp?.hooks?._filters?.['editor.BlockEdit']?.['riman/cover-poster/controls']);

// Cover Lazy Script geladen?
console.log('Lazy Script:', !!document.querySelector('script[src*="cover-lazy-video"]'));

// CSS geladen?
console.log('Lazy CSS:', !!document.querySelector('link[href*="cover-lazy-video"]'));
```

## Erwartete Ergebnisse

### ✅ Erfolgreich:
- Poster-Bilder laden sofort (< 100ms)
- Videos laden verzögert/on-demand
- LCP-Score verbessert sich
- Smooth Übergang Poster → Video
- Block Editor zeigt Poster-Panel

### ❌ Probleme:
- Videos laden sofort
- Keine Poster-Bilder sichtbar
- Ruckelige Übergänge
- Fehlende Editor-Panels