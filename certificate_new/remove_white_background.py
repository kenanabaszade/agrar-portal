from PIL import Image

def remove_white_background(input_path, output_path, threshold=240):
    """
    Ağ fonu şəffaf edən funksiya.
    threshold - ağ rəngin intensivlik səviyyəsi (0-255). 
    Daha aşağı qoysan, az silər.
    """
    img = Image.open(input_path).convert("RGBA")
    datas = img.getdata()

    new_data = []
    for item in datas:
        # Əgər piksel ağ rəngə yaxındırsa — şəffaf elə
        if item[0] > threshold and item[1] > threshold and item[2] > threshold:
            new_data.append((255, 255, 255, 0))
        else:
            new_data.append(item)

    img.putdata(new_data)
    img.save(output_path, "PNG")
    print(f"✅ Yeni şəffaf şəkil yaradıldı: {output_path}")

# Nümunə istifadə:
remove_white_background("aqrar_x.png", "aqrar_x1.png")
