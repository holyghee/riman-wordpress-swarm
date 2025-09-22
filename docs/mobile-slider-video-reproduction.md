# Mobile Service-Cards Slider – Video Playback Rekonstruktion

Dieses Dokument fasst zusammen, wie der mobile Service-Cards-Slider zuvor die Videos abgespielt hat und welche Schritte notwendig sind, damit ein weiterer Codex/Claude-Agent diese Lösung (ohne erneute Fehlversuche) rekonstruieren kann. Es werden keine Codeänderungen vorgenommen; die Hinweise dienen ausschließlich der Wiederherstellung des funktionierenden Stands.

## 1. Überblick über die beteiligten Komponenten

- **Block-Rendering (`wp-content/plugins/riman-blocks/blocks/service-cards.php`)**
  - Rendert die Karten, setzt `data-mobile-slider`, `data-slider-autoplay`, `data-slider-interval`, `data-video-src` und `data-poster-url`.
  - Enqueued folgende Assets, sobald mindestens eine Karte ein Video besitzt:
    - `service-cards-video.css`
    - `service-cards-video-sequence.js`
  - Aktiviert bei gesetztem „Mobiler Slider“-Attribut zusätzlich
    - `service-cards-mobile-slider.css`
    - `service-cards-mobile-slider.js`
- **Editor-Einstellungen (`assets/service-cards-block.js`)**
  - Stellt die UI-Schalter für `mobileSlider`, `sliderAutoplay`, `sliderInterval` etc. bereit.
  - Wichtig: Bei Änderungen im Editor immer per „Aktualisieren“ sichern, damit die Attribute im Frontend ankommen.
- **Frontend-JS – Slider (`assets/service-cards-mobile-slider.js`)**
  - Baut auf mobilen Viewports (<= 780 px) das Grid in einen Slider um.
  - Klont die Karten (inkl. Video-Markup) in `.riman-service-slider-track` und steuert Slide-Wechsel.
  - Ruft `handleVideoPlayback()` nach jedem Slidewechsel auf; dort werden aktive Videos gestartet und andere zurückgesetzt.
- **Frontend-JS – Video-Sequenz (`assets/service-cards-video-sequence.js`)**
  - Lädt die tatsächlichen Video-Quellen aus `data-video-src` in die `<video>`-Elemente.
  - Steuert die Sequenzierung: immer nur ein Video gleichzeitig, max. 5 s, dann nächstes.
  - Enthält spezielle Pfade für mobile Slider (`startMobileSliderSequence`, `handleSliderChange`) und überschreibt Styles für mobile Geräte.
- **Video-Styling (`assets/service-cards-video.css`)**
  - Sorgt dafür, dass Poster sichtbar bleiben bis das Video spielt und dass Videos im Slider nicht vom Clip-Path abgeschnitten werden.

## 2. Notwendige Voraussetzungen im Block

1. **Mobiler Slider aktivieren**
   - Im Block-Inspector „Mobiler Slider (<= 780 px)“ einschalten.
   - Optional „Autoplay“ samt Interval (Standard 5000 ms) aktivieren.
   - Prüfen, dass die veröffentlichten Attribute in der gerenderten Seite vorhanden sind (`data-mobile-slider="true"`).
2. **Video-Metadaten je Karte**
   - Pro RIMAN-Seite `_riman_featured_video_id` oder `_riman_featured_video_url` hinterlegen.
   - Optional: Poster-Bild über Featured Image, damit der LCP-Pfad funktioniert.
3. **Caches leeren**
   - Nach Änderungen Cache leeren oder einmal `wp cache flush`, damit `core_block_types` nicht alte Blockdefinitionen liefert.

## 3. Frontend-Initialisierung (so funktionierte es zuvor)

1. **Timing-Reihenfolge sicherstellen**
   - `service-cards-mobile-slider.js` läuft mit `DOMContentLoaded` und baut den Slider sofort um.
   - `service-cards-video-sequence.js` wartet 1 s (`setTimeout(..., 1000)`), greift dann bereits auf die Slider-Struktur zu, lädt die Videos (`video.src = data-video-src`) und startet die Sequenz.
2. **Slider-Spezifischer Video-Start**
   - `setupVideoSequence()` erkennt per `container.dataset.mobileSlider === 'true'`, dass es sich um den mobilen Slider handelt, und ruft `startMobileSliderSequence()`.
   - Der Start stößt `playNextVideo(cards)` an; das erste Video erhält `video.classList.add('is-playing')` und `card.classList.add('video-active')`, womit die CSS-Overrides greifen.
3. **Slide-Wechsel synchronisieren**
   - Über den (notwendigen!) `MutationObserver` in `observeSliderChanges()` werden Klassenänderungen am Slider-Wrapper registriert.
   - Bei jeder Mutation ruft die Sequenz `handleSliderChange(cards)` und setzt `currentVideoIndex` zurück, sodass nach jedem Swipe das neue Video geladen/gestartet wird.
   - **Wichtig:** Damit dieser Mechanismus aktiv ist, muss `observeSliderChanges()` nach `setupVideoSequence()` aufgerufen werden. Falls das im aktuellen Build fehlt, war dies der entscheidende Patch im früheren Stand.
4. **Style-Korrekturen für Mobile**
   - JS erzwingt für mobile Videos `position: relative`, `min-height: 200px`, `overflow: visible` auf dem Media-Container.
   - CSS in `service-cards-video.css` sorgt für `opacity: 1 !important` sobald `.is-playing` gesetzt ist.

## 4. Prüfschritte zur Verifikation

1. **Browser-Konsole beobachten**
   - Beide JS-Dateien loggen umfangreich (z. B. „Making videos visible…“, „Starting mobile slider video sequence…“).
   - Wenn Logs fehlen, ist das Script nicht geladen → Block-Attribute / Enqueues prüfen.
2. **DOM-Check**
   - In der aktiven Slide muss `<video class="riman-card-video is-playing" src="…">` stehen, Poster soll `opacity: 0` haben.
   - Slider-Wrapper benötigt `.riman-mobile-slider-active`.
3. **Slider-Interaktion**
   - Nach Swipe (Touch/Mouse) sollte innerhalb ~300 ms das neue Video sichtbar werden.
   - Wenn Videos beim zweiten Slide nicht mehr starten: prüfen, ob der MutationObserver aktiv ist (`videoSequenceInstance.observerInstances` in DevTools inspizieren).
4. **Fallback**
   - Falls Videos wegen Autoplay-Richtlinien blockieren: Sicherstellen, dass `muted`, `playsinline` und `preload="none"` gesetzt bleiben (werden serverseitig gerendert).

## 5. Mögliche Regression im aktuellen Stand

- Wenn die Videos derzeit nicht anlaufen, obwohl alles oben erfüllt ist, fehlt wahrscheinlich der Aufruf von `observeSliderChanges()` (oder eine vergleichbare Reinitialisierung) nach dem Umbau zum Slider.
- In der funktionierenden Session wurde genau dieser Schritt ergänzt, damit der MutationObserver die Slider-Klassen überwacht und `handleSliderChange` auslöst. Ohne den Observer friert `currentVideoIndex` ein und nur das erste Video spielt.

## 6. Vorgehen für einen neuen Agenten

1. **Status prüfen**
   - Seite im mobilen Viewport öffnen, DevTools-Konsole auf Fehler prüfen.
   - Sicherstellen, dass alle o. g. Datenattribute vorhanden sind.
2. **Observer-Reinitialisierung wiederherstellen**
   - Im JS nachvollziehen, ob `observeSliderChanges()` nach `setupVideoSequence()` ausgelöst wird. Falls nicht, denselben Call erneut hinzufügen (dies war vermutlich der verlorene Patch).
3. **Manuelles Retest**
   - Seite neu laden, Slider swipen und prüfen, ob `video-active` + `is-playing` auf der jeweils aktiven Karte sitzen.
   - Bei Erfolg optional Logs reduzieren.

Mit diesen Schritten lässt sich der frühere Stand nachvollziehen und zielgerichtet wiederherstellen, ohne erneut „auf Verdacht“ an verschiedenen Stellen zu ändern.
