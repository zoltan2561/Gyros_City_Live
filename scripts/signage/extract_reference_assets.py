"""Extract price-free artwork from the user-supplied reference videos."""
import argparse, hashlib, io, json, subprocess
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter

root=Path(__file__).resolve().parents[2]
out=root/'public/signage/reference-assets';out.mkdir(exist_ok=True)
parser=argparse.ArgumentParser(description=__doc__)
for name in ('burger','menu','pizza'):parser.add_argument('--'+name,required=True,type=Path)
arguments=parser.parse_args()
sources={name:getattr(arguments,name) for name in ('burger','menu','pizza')}

def frame(source,time):
 return Image.open(io.BytesIO(subprocess.check_output(['ffmpeg','-hide_banner','-loglevel','error','-ss',str(time),'-i',str(sources[source]),'-frames:v','1','-f','image2pipe','-vcodec','png','-']))).convert('RGB')

def food_hull(image,limit):
 # Recover a smooth food silhouette from large connected food-colour regions.
 # Small bright ember components are discarded before taking the convex outline.
 small=image.convert('RGB').resize((image.width//3,image.height//3));bound=limit.resize(small.size);w,h=small.size
 pixels=small.load();allowed=bound.load();remaining=set()
 for y in range(h):
  for x in range(w):
   r,g,b=pixels[x,y]
   if allowed[x,y]>128 and g>68 and r>88 and g>b*.9:remaining.add((x,y))
 points=[]
 while remaining:
  seed=remaining.pop();component=[seed];queue=[seed]
  while queue:
   x,y=queue.pop()
   for point in [(x+1,y),(x-1,y),(x,y+1),(x,y-1),(x+1,y+1),(x-1,y-1),(x+1,y-1),(x-1,y+1)]:
    if point in remaining:remaining.remove(point);queue.append(point);component.append(point)
  if len(component)>max(35,w*h*.002):points.extend(component)
 points=sorted(set(points))
 if len(points)<3:return limit
 def cross(o,a,b):return (a[0]-o[0])*(b[1]-o[1])-(a[1]-o[1])*(b[0]-o[0])
 lower=[];upper=[]
 for p in points:
  while len(lower)>=2 and cross(lower[-2],lower[-1],p)<=0:lower.pop()
  lower.append(p)
 for p in reversed(points):
  while len(upper)>=2 and cross(upper[-2],upper[-1],p)<=0:upper.pop()
  upper.append(p)
 mask=Image.new('L',image.size);ImageDraw.Draw(mask).polygon([(x*3,y*3) for x,y in lower[:-1]+upper[:-1]],fill=255)
 return mask.filter(ImageFilter.MaxFilter(3)).filter(ImageFilter.GaussianBlur(.6))

def cutout(image,background):
 if background=='ember':
  result=image.convert('RGBA');w,h=image.size;mask=Image.new('L',(w,h));draw=ImageDraw.Draw(mask)
  points=[(0,.30),(.12,.14),(.30,.035),(.47,0),(.71,.025),(.87,.14),(.97,.35),(1,.53),(.93,.75),(.78,.9),(.59,.99),(.30,.98),(.09,.84),(0,.64)]
  draw.polygon([(round(x*w),round(y*h)) for x,y in points],fill=255)
  draw.rectangle((950,0,w,45),fill=0);draw.rectangle((1275,495,w,h),fill=0)
  result.putalpha(food_hull(image,mask));return result
 # Flood only exterior background. Food highlights/shadows enclosed by the silhouette survive.
 rgb=image.load();w,h=image.size
 mask=Image.new('L',image.size,255);px=mask.load()
 for y in range(h):
  for x in range(w):
   r,g,b=rgb[x,y]
   is_bg=(min(r,g,b)>105) if background=='white' else max(abs(r-background),abs(g-background),abs(b-background))<38
   if is_bg:px[x,y]=0
 for x,y in [(0,0),(w-1,0),(0,h-1),(w-1,h-1)]:
  if px[x,y]==0:ImageDraw.floodfill(mask,(x,y),128)
 alpha=mask.point(lambda v:0 if v==128 else 255)
 # Discard isolated embers/background specks, retaining the connected food silhouette.
 ImageDraw.floodfill(alpha,(w//2,h//2),128)
 alpha=alpha.point(lambda v:255 if v==128 else 0).filter(ImageFilter.GaussianBlur(.5))
 result=image.convert('RGBA');result.putalpha(alpha)
 box=alpha.getbbox()
 return result.crop(box) if box else result

manifest={}
def save(key,image):
 image.save(out/f'{key}.webp',quality=95,method=6)
 register(key,f'{key}.webp')

def register(key,name):
 path=out/name;data=path.read_bytes();digest=hashlib.sha256(data).hexdigest()
 immutable=f'{path.stem}-{digest}{path.suffix}'
 if not (out/immutable).exists():(out/immutable).write_bytes(data)
 manifest[key]=immutable

save('embers',frame('burger',0))
save('smoke',frame('pizza',0))
burger=frame('burger',3);white=frame('menu',3)
boxes={60:(55,0,620,488),61:(690,12,1310,480),62:(1340,0,1920,486),63:(80,580,640,1000),65:(770,565,1215,1007),64:(1310,575,1910,1000)}
white_boxes={60:(70,12,433,286),61:(488,14,896,286),62:(899,2,1280,286),63:(75,365,444,643),65:(495,360,870,643),64:(892,360,1278,643)}
shape=[(.42,0),(.65,.01),(.83,.07),(.93,.20),(.98,.35),(.95,.45),(1,.61),(.95,.80),(.82,.95),(.56,1),(.29,.98),(.08,.88),(0,.7),(.015,.52),(.09,.40),(.11,.22),(.24,.06)]
for item,box in boxes.items():
 image=burger.crop(box).convert('RGBA');w,h=image.size;mask=Image.new('L',image.size)
 ImageDraw.Draw(mask).polygon([(int(x*w),int(y*h)) for x,y in shape],fill=255);image.putalpha(food_hull(image,mask))
 save(f'burger-{item}',image);save(f'menu-{item}',white.crop(white_boxes[item]))
for index,item in enumerate([336,342,341,339,333,335,334,332,338,329]):
 image=frame('pizza',50.5+20*index)
 save(f'pizza-{item}',cutout(image.crop((0,245,1335,1070)),'ember'))
 print('pizza',item,flush=True)
for name,time in [('bundles',25),('gyros',45),('fried',65),('sides',83),('kebab',91)]:
 image=frame('menu',time)
 image.save(root/f'.playwright-mcp/reference-menu-{name}.png')
 pages={'bundles':([135,136,137,140,139,138,134],4),'gyros':([128,133,156,66,76,77,78,153],4),'fried':([161,167,158,165,160,159,282,248],4),'sides':([95,93,169,170,174,173],3)}
 if name in pages:
  ids,columns=pages[name]
  for i,item in enumerate(ids):
   col=i%columns;row=i//columns
   if name=='bundles' and row:
    box=[(145,368,437,646),(487,368,793,646),(798,368,1143,646)][col]
   else:
    end=280 if row==0 else (658 if name=='sides' else 620)
    box=(int(col*1280/columns),0 if row==0 else 360,int((col+1)*1280/columns),end)
   save(f'menu-{item}',image.crop(box))
   if item==173:save('promo-dessert',cutout(image.crop(box),82))
 else:
  save('wood',image.crop((130,695,1150,715)))
  for item,box in {121:(160,40,660,292),124:(684,35,1140,293),119:(160,365,480,585),120:(485,370,807,586),123:(805,360,1135,586)}.items():save(f'menu-{item}',image.crop(box))
for key,name in [('display-font','BebasNeue-Regular.ttf'),('script-font','Satisfy-Regular.ttf'),('grunge-mask','grunge-mask.svg')]:register(key,name)
(out/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
print('assets',len(manifest),flush=True)
