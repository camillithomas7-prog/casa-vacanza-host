// Resort map geo helper — pure functions, no dependencies.
// Calibration uses 3 reference points (fraction 0..1 of map width/height + GPS lat/lng).
// Builds an affine transform that maps GPS <-> map fraction.
(function (global) {
  function det3(m) {
    return m[0][0] * (m[1][1] * m[2][2] - m[1][2] * m[2][1])
         - m[0][1] * (m[1][0] * m[2][2] - m[1][2] * m[2][0])
         + m[0][2] * (m[1][0] * m[2][1] - m[1][1] * m[2][0]);
  }

  // refs: [{px,py,lat,lng}, {...}, {...}]
  // Returns { a,b,c,d,e,f } such that:
  //   px = a*lng + b*lat + c
  //   py = d*lng + e*lat + f
  function solveAffine(refs) {
    if (!Array.isArray(refs) || refs.length < 3) return null;
    const p1 = refs[0], p2 = refs[1], p3 = refs[2];
    if ([p1, p2, p3].some(p => p == null || !isFinite(p.lat) || !isFinite(p.lng) || !isFinite(p.px) || !isFinite(p.py))) return null;
    const D = det3([[p1.lng, p1.lat, 1], [p2.lng, p2.lat, 1], [p3.lng, p3.lat, 1]]);
    if (Math.abs(D) < 1e-15) return null;
    const Da = det3([[p1.px, p1.lat, 1], [p2.px, p2.lat, 1], [p3.px, p3.lat, 1]]);
    const Db = det3([[p1.lng, p1.px, 1], [p2.lng, p2.px, 1], [p3.lng, p3.px, 1]]);
    const Dc = det3([[p1.lng, p1.lat, p1.px], [p2.lng, p2.lat, p2.px], [p3.lng, p3.lat, p3.px]]);
    const Dd = det3([[p1.py, p1.lat, 1], [p2.py, p2.lat, 1], [p3.py, p3.lat, 1]]);
    const De = det3([[p1.lng, p1.py, 1], [p2.lng, p2.py, 1], [p3.lng, p3.py, 1]]);
    const Df = det3([[p1.lng, p1.lat, p1.py], [p2.lng, p2.lat, p2.py], [p3.lng, p3.lat, p3.py]]);
    return { a: Da / D, b: Db / D, c: Dc / D, d: Dd / D, e: De / D, f: Df / D };
  }

  function gpsToFrac(aff, lat, lng) {
    if (!aff) return null;
    return { x: aff.a * lng + aff.b * lat + aff.c, y: aff.d * lng + aff.e * lat + aff.f };
  }

  function fracToGps(aff, x, y) {
    if (!aff) return null;
    const det = aff.a * aff.e - aff.b * aff.d;
    if (Math.abs(det) < 1e-15) return null;
    return {
      lng: ((x - aff.c) * aff.e - (y - aff.f) * aff.b) / det,
      lat: ((y - aff.f) * aff.a - (x - aff.c) * aff.d) / det
    };
  }

  function haversineMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000, toRad = d => d * Math.PI / 180;
    const dLat = toRad(lat2 - lat1), dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function bearingDeg(lat1, lng1, lat2, lng2) {
    const toRad = d => d * Math.PI / 180;
    const y = Math.sin(toRad(lng2 - lng1)) * Math.cos(toRad(lat2));
    const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) - Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(toRad(lng2 - lng1));
    return ((Math.atan2(y, x) * 180 / Math.PI) + 360) % 360;
  }

  function formatDistance(m) {
    if (!isFinite(m)) return '–';
    if (m < 50) return Math.round(m) + ' m';
    if (m < 1000) return Math.round(m / 5) * 5 + ' m';
    return (m / 1000).toFixed(2) + ' km';
  }

  global.ResortGeo = { solveAffine, gpsToFrac, fracToGps, haversineMeters, bearingDeg, formatDistance };
})(window);
