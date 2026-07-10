(function () {
  const TARGET_WIDTH = 800;
  const TARGET_HEIGHT = 240;
  const TARGET_PADDING = 20;
  const MAX_SOURCE_EDGE = 8000;
  const MAX_SOURCE_PIXELS = 40000000;

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function loadImage(file) {
    return new Promise(function (resolve, reject) {
      const image = new Image();
      const objectUrl = URL.createObjectURL(file);

      image.onload = function () {
        URL.revokeObjectURL(objectUrl);
        resolve(image);
      };
      image.onerror = function () {
        URL.revokeObjectURL(objectUrl);
        reject(new Error("Impossible de lire cette image."));
      };
      image.src = objectUrl;
    });
  }

  function estimateBackground(data, width, height) {
    const sampleSize = Math.max(2, Math.min(12, Math.floor(Math.min(width, height) / 12)));
    const origins = [
      [0, 0],
      [Math.max(0, width - sampleSize), 0],
      [0, Math.max(0, height - sampleSize)],
      [Math.max(0, width - sampleSize), Math.max(0, height - sampleSize)],
    ];
    let red = 0;
    let green = 0;
    let blue = 0;
    let count = 0;

    origins.forEach(function (origin) {
      for (let y = origin[1]; y < origin[1] + sampleSize; y += 1) {
        for (let x = origin[0]; x < origin[0] + sampleSize; x += 1) {
          const offset = (y * width + x) * 4;
          if (data[offset + 3] < 24) continue;
          red += data[offset];
          green += data[offset + 1];
          blue += data[offset + 2];
          count += 1;
        }
      }
    });

    if (count === 0) {
      return [255, 255, 255];
    }

    return [red / count, green / count, blue / count];
  }

  function processPixels(context, width, height) {
    const imageData = context.getImageData(0, 0, width, height);
    const pixels = imageData.data;
    const background = estimateBackground(pixels, width, height);
    let minX = width;
    let minY = height;
    let maxX = -1;
    let maxY = -1;

    for (let y = 0; y < height; y += 1) {
      for (let x = 0; x < width; x += 1) {
        const offset = (y * width + x) * 4;
        const sourceAlpha = pixels[offset + 3] / 255;
        if (sourceAlpha <= 0.02) {
          pixels[offset + 3] = 0;
          continue;
        }

        const redDistance = pixels[offset] - background[0];
        const greenDistance = pixels[offset + 1] - background[1];
        const blueDistance = pixels[offset + 2] - background[2];
        const distance = Math.sqrt(
          redDistance * redDistance
          + greenDistance * greenDistance
          + blueDistance * blueDistance
        );
        const inkStrength = clamp((distance - 12) / 62, 0, 1) * sourceAlpha;

        if (inkStrength < 0.08) {
          pixels[offset + 3] = 0;
          continue;
        }

        pixels[offset] = 31;
        pixels[offset + 1] = 41;
        pixels[offset + 2] = 55;
        pixels[offset + 3] = Math.round(255 * inkStrength);
        minX = Math.min(minX, x);
        minY = Math.min(minY, y);
        maxX = Math.max(maxX, x);
        maxY = Math.max(maxY, y);
      }
    }

    context.putImageData(imageData, 0, 0);

    if (maxX < minX || maxY < minY) {
      throw new Error("Aucun trait de signature suffisamment visible n’a été détecté.");
    }

    return {
      x: minX,
      y: minY,
      width: maxX - minX + 1,
      height: maxY - minY + 1,
    };
  }

  function canvasToPngFile(canvas, originalName) {
    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (blob) {
        if (!blob) {
          reject(new Error("La conversion PNG de la signature a échoué."));
          return;
        }

        const baseName = (originalName || "signature")
          .replace(/\.[^.]+$/, "")
          .replace(/[^a-z0-9_-]+/gi, "-")
          .replace(/^-+|-+$/g, "") || "signature";
        resolve(new File([blob], `${baseName}-800x240.png`, {
          type: "image/png",
          lastModified: Date.now(),
        }));
      }, "image/png");
    });
  }

  async function normalize(file) {
    if (!(file instanceof File)) {
      throw new Error("Aucun fichier de signature sélectionné.");
    }

    const image = await loadImage(file);
    if (
      image.naturalWidth < 1
      || image.naturalHeight < 1
      || image.naturalWidth > MAX_SOURCE_EDGE
      || image.naturalHeight > MAX_SOURCE_EDGE
      || image.naturalWidth * image.naturalHeight > MAX_SOURCE_PIXELS
    ) {
      throw new Error("Image trop grande. Utilisez une capture inférieure à 40 mégapixels.");
    }

    const workScale = Math.min(1, 1800 / Math.max(image.naturalWidth, image.naturalHeight));
    const workWidth = Math.max(1, Math.round(image.naturalWidth * workScale));
    const workHeight = Math.max(1, Math.round(image.naturalHeight * workScale));
    const workCanvas = document.createElement("canvas");
    workCanvas.width = workWidth;
    workCanvas.height = workHeight;
    const workContext = workCanvas.getContext("2d", { willReadFrequently: true });
    if (!workContext) {
      throw new Error("Le navigateur ne permet pas de traiter cette image.");
    }
    workContext.drawImage(image, 0, 0, workWidth, workHeight);
    const bounds = processPixels(workContext, workWidth, workHeight);

    const output = document.createElement("canvas");
    output.width = TARGET_WIDTH;
    output.height = TARGET_HEIGHT;
    const outputContext = output.getContext("2d");
    if (!outputContext) {
      throw new Error("Le navigateur ne permet pas de créer la signature.");
    }
    outputContext.clearRect(0, 0, TARGET_WIDTH, TARGET_HEIGHT);

    const availableWidth = TARGET_WIDTH - TARGET_PADDING * 2;
    const availableHeight = TARGET_HEIGHT - TARGET_PADDING * 2;
    const scale = Math.min(availableWidth / bounds.width, availableHeight / bounds.height);
    const drawWidth = Math.max(1, Math.round(bounds.width * scale));
    const drawHeight = Math.max(1, Math.round(bounds.height * scale));
    const drawX = Math.round((TARGET_WIDTH - drawWidth) / 2);
    const drawY = Math.round((TARGET_HEIGHT - drawHeight) / 2);
    outputContext.drawImage(
      workCanvas,
      bounds.x,
      bounds.y,
      bounds.width,
      bounds.height,
      drawX,
      drawY,
      drawWidth,
      drawHeight
    );

    return canvasToPngFile(output, file.name);
  }

  window.CertificateSignatureImage = {
    normalize: normalize,
    width: TARGET_WIDTH,
    height: TARGET_HEIGHT,
  };
})();
