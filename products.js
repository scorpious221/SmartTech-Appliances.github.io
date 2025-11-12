/* ---------- PRODUCT DATA (all products from your program) ---------- */
/* Each product: { id, name, price, img, category, sub } */
const PRODUCTS = [
  // Household -> Security Devices
  {id:'smoke', name:'Smoke Detector', price:2500, img:'images/smokedetector.JPG', category:'household', sub:'Security Devices', desc:`A smoke detector is a safety device that senses smoke and sounds an alarm to warn of a potential fire. It uses technologies like photoelectric or ionization to detect smoke particles in the air, triggering a loud, high-pitched tone and flashing light.
  <br><br><strong>Features:</strong><br>
  • Offers reliable fire risk detection and early warning <br>
  • Easy installation. <br>
  • Battery life up to 10 years <br>
  • Emits a loud 84dB sound<br>
  • Operates effectively from 10°C–50°C.`},
  {id:'motion', name:'Motion Sensor', price:5330, img:'images/motionsensor.jpg', category:'household', sub:'Security Devices', desc:`The sensor is suitable for home lighting, indoor and outdoor lighting fixtures. It can be adjusted to be used all day or at night. When someone enters the sensing range of the switch, the sensor detects changes in the infrared spectrum of the human body, and the switch automatically switches on the load. The light turns on when the person arrives, and the light turns off when the person leaves. It is friendly, convenient, safe, and energy-saving.
<br><br><strong>FEATURES:</strong><br>
• Stable performance, intelligent light control, delayed shutdown<br>
• High sensitivity, 360 °full range detection without blind spots<br>
• Ceiling mounted, bottom bracket design, quick wiring for easy installation and disassembly<br>
• Fireproof and flame-retardant material: effectively preventing fire hazards, not deformed, not easily oxidized, safe, durable, and durable<br>
• Wide application range: can be used for switch control of lighting fixtures in various buildings, such as stairs, corridors, restrooms, school communities, etc.`},
  {id:'doorknob', name:'Smart Doorknob', price:10350, img:'images/smartdoorknob.jpg', category:'household', sub:'Security Devices', desc:`A smart doorknob offers keyless entry via fingerprint, keypad, or smartphone app, providing features like remote access, temporary codes, and auto-locking for enhanced security and convenience. The best choice depends on your needs for features like biometric access, remote control, and ease of installation. 
<br><br><strong>Features:</strong><br>
• 5 Unlocking Methods <br>
• APP Functions <br>
• Remote Unlocking <br>
• Temporary Password <br>
• Anti Peeping Password <br>
• Passage Mode <br>
• Volume Adjusting <br>
• System Language <br>
• Direction Adjustment <br>
• USB Interface <br>
• Connect Gateway`},
  {id:'doorbell', name:'Video Doorbell', price:13500, img:'images/vidbell.jpg', category:'household', sub:'Security Devices', desc: `A video doorbell is a smart security device that lets homeowners see, hear, and speak to visitors through a built-in camera, microphone, and speaker, enhancing home safety by recording footage and sending instant motion alerts.
<br><br><strong>Features:</strong><br>
• Built-in HD camera with live video streaming <br> 
• Two-way audio communication <br>
• Motion detection with instant alerts <br>
• Night vision for clear images in low light <br>
• Wi-Fi connectivity for remote access via smartphone <br>
• Cloud or local video storage options <br>
• Easy installation on most door frames`},
  {id:'cctv', name:'CCTV Camera', price:8900, img:'images/cctv.jpg', category:'household', sub:'Security Devices', desc: `A CCTV (Closed-Circuit Television) camera is a security device that monitors and records activities in a specific area by transmitting video signals to a monitor or recording device, helping deter crime and enhance safety in homes, offices, and public spaces.
<br><br><strong>Features:</strong><br>
• High-Definition Video <br>
• Pan and Tilt: 360º horizontal and 114º vertical range <br>
• Advanced Night Vision <br>
• Motion Detection and Notifications <br>
• Sound and Light Alarm<br>
• Two-Way Audio<br>
• Stores up to 256 GB on a microSD card`},

  // Household -> Utilities
  {id:'kettle', name:'Electric Kettle', price:1080, img:'images/kettle.jpg', category:'household', sub:'Utilities', desc: ` An electric kettle is a kitchen appliance that quickly boils water using electric power, featuring a built-in heating element that automatically switches off when the water reaches its boiling point, offering convenience, speed, and energy efficiency for making coffee, tea, or instant meals.
<br><br><strong>Features:</strong><br>
• Fast boiling with automatic shut-off function <br> 
• Made with heat-resistant and durable materials <br> 
• Indicator light to show when the kettle is in use <br>
• 360° swivel base for easy handling <br>
• Built-in safety feature to prevent dry boiling <br>
• Available in different capacities and designs `},
  {id:'solar', name:'Solar Panel', price:1590, img:'images/solarpanel.jpg', category:'household', sub:'Utilities', desc: `A solar panel is a device that converts sunlight into electrical energy using photovoltaic (PV) cells, providing a renewable and eco-friendly power source for homes, businesses, and outdoor applications while reducing electricity costs and dependence on non-renewable energy sources.
<br><br><strong>Features:</strong><br>
• High-efficiency photovoltaic cells for maximum energy output <br>
• Durable and weather-resistant materials for long-lasting use <br>
• Suitable for residential, commercial, and industrial installations <br>
• Easy to mount on rooftops or open areas <br>
• Low maintenance and environmentally friendly <br> 
• Available in different wattage capacities and sizes <br> `},
  {id:'sewing', name:'Sewing Machine', price:38000, img:'images/sewingmachine.jpg', category:'household', sub:'Utilities', desc: `A sewing machine is a versatile home or professional appliance used to stitch fabric and other materials quickly and precisely. It simplifies garment repair, crafting, and creative sewing projects with consistent and durable stitches.
<br><br><strong>Features:</strong><br>
• Variety of stitches for repairs and decorative sewing <br>
• Adjustable stitch length and width <br>
• Durable and long-lasting construction <br>
• Easy-to-use controls for all skill levels <br>
• Suitable for different fabrics and sewing projects`},
  {id:'avr', name:'Voltage Regulator', price:18899, img:'images/voltagereg.jpg', category:'household', sub:'Utilities', desc:` A voltage regulator is an electrical device that maintains a constant voltage level to protect equipment from fluctuations, ensuring electronic devices receive stable and safe power and preventing damage from overvoltage or undervoltage in computers, appliances, and industrial machines.
<br><br><strong>Features:</strong><br>
• Automatically stabilizes input and output voltage <br> 
• Protects devices from power surges and fluctuations <br> 
• High efficiency and fast response time <br>  
• Compact and durable design for long-term use <br> 
• Suitable for home, office, and industrial applications <br> 
• Available in various voltage capacities`},
  {id:'generator', name:'Portable Generator', price:10000, img:'images/portablegen.jpg', category:'household', sub:'Utilities', desc:` A portable generator is a compact and versatile power supply that provides electricity wherever you need it, ideal for outdoor activities, emergencies, or backup home power. Its portable design allows easy transport while delivering reliable energy for various devices and appliances.
<br><br><strong>Features:</strong><br>
• Fastest charging on the market <br>
• Power for all important devices <br>
• Long-lasting LFP (lithium iron phosphate) battery <br>
• Fast charging with solar <br>
• Lightweight & portable`},

  // Household -> Home Office
  {id:'printer', name:'Printer', price:23000, img:'images/printer.jpg', category:'household', sub:'Home Office', desc:`A printer is an essential office or home device that produces high-quality hard copies of documents, images, and graphics. It simplifies printing tasks, making it quick and convenient to create professional-looking materials.
<br><br><strong>Features:</strong><br>
• Print, scan, and copy functions in one device <br>
• Refillable EcoTank system for cost-efficient printing <br>
• High-quality color and black-and-white printing <br>
• Wireless and USB connectivity for easy access <br>
• Fast printing speed and low power consumption <br>
• Compact design with easy-to-use control panel`},
  {id:'shredder', name:'Paper Shredder', price:16888, img:'images/papershredder.jpg', category:'household', sub:'Home Office', desc:`A paper shredder is an office device that cuts paper into small strips or particles to securely destroy sensitive documents, helping prevent identity theft and protecting personal or business information in offices, schools, and homes.
<br><br><strong>Features:</strong><br>
• Can shred: Card, Paper Clip, Staple <br>
• Continuous run time: 10min <br>
• Bin capacity: 23L <br>
• Machine noise: 60dB <br>
• Machine power: 180W <br>
• German level 4 confidentiality <br>
• Nitride steel knife, sharp and durable <br>
• Intelligent induction, Overheat protection, Stop protection, Auto reverse <br>
• 23L Visible Paper Bucket <br>
• Simple design, quick operation`},
  {id:'laminator', name:'Laminator', price:4550, img:'images/laminator.jpg', category:'household', sub:'Home Office', desc:`A laminator is a machine that seals and protects documents, photos, or cards between layers of plastic film, preserving them from moisture, dirt, and wear, and is commonly used in offices, schools, and print shops for durable, professional-looking results. 
<br><br><strong>Features:</strong><br>
• Suitable for photos, ID cards, employee cards, and documents <br>
• Adjustable temperature up to 150°C for different materials <br>
• Cold lamination function for heat-sensitive films <br>
• Reverse function to eject documents if needed <br>
• Easy to operate and resistant to high temperatures <br>
• Modern design ideal for office use` },
  {id:'photocopy', name:'Photocopy Machine', price:28999, img:'images/photocopy.jpg', category:'household', sub:'Home Office', desc:`A photocopy machine is an office device that quickly and efficiently produces duplicate copies of documents and images using light, heat, and toner, commonly used in offices, schools, and businesses for reports, forms, and other documents. 
<br><br><strong>Features:</strong><br>
• Printing up to 36ppm <br>
• 3-in-1 multi-function printer with print, copy and scan capabilities <br>
• 50-page Automatic Document Feeder saves time with multi-page copy and scan <br>
• Build-in dual band wireless module (2.4GHz / 5GHz), Ethernet, and mobile device printing <br>
• Refresh EZ Print Subscription Ready`},
  {id:'multiport', name:'Multi-Port Hubs', price:7999, img:'images/multiport.jpg', category:'household', sub:'Home Office', desc:`A multi-port hub expands your computer’s connectivity by allowing multiple devices to connect through one port. It’s perfect for plugging in USB drives, keyboards, mice, and other accessories, making multitasking easier and more efficient. 
<br><br><strong>Features:</strong><br>
• Multiple USB ports for simultaneous connections <br>
• High-speed data transfer <br>
• Compact and portable design <br>
• Plug-and-play compatibility`},

  // Entertainment -> Televisions & Displays
  {id:'lcd', name:'LCD Monitor', price:8900, img:'images/lcd.jpg', category:'entertainment', sub:'Televisions & Displays', desc:` Experience stunning visuals with this 24-inch Full HD monitor featuring a sleek 3-side borderless design. Its vibrant display and wide viewing angles provide an immersive experience for work, entertainment, and gaming. The modern, frameless look adds a stylish touch to any setup.
<br><br><strong>Features:</strong><br>
• IPS Display <br>
• Flicker Safe <br>
• Reader Mode <br>
• 24-inch Full HD <br>
• Response time: 5ms <br>
• Smart Energy Saving <br>
• Resolution: 1920 x 1080`},
  {id:'smarttv', name:'Smart TV', price:10679, img:'images/stv.jpg', category:'entertainment', sub:'Televisions & Displays', desc:` The Smart TV A 2016 offers an impressive viewing experience with its 32-inch HD display and powerful Dolby Audio sound. Designed for modern entertainment, it allows users to stream content, browse apps, and enjoy movies with rich visuals and crystal-clear sound quality. Perfect for home entertainment and daily viewing.
<br><br><strong>Features:</strong><br>
• 32-inch HD screen with vibrant and sharp picture quality <br>
• Dolby Audio technology for immersive sound experience <br>
• Built-in Wi-Fi for streaming and smart app access <br>
• Sleek, slim design suitable for any room <br>
• Multiple connectivity options (HDMI, USB, AV input) <br>
• Energy-efficient performance with easy-to-use interface`},
  {id:'projector', name:'Projector', price:36000, img:'images/proj.jpg', category:'entertainment', sub:'Televisions & Displays', desc:`A projector is an electronic device that displays images or videos onto a large screen or surface, turning any room into a home theater, classroom, or presentation space. It provides a larger, clear, and immersive viewing experience for movies, presentations, and events.
<br><br><strong>Features:</strong><br>
• 3LCD technology for bright, vivid image quality <br>
• 3,600 lumens of colour and white brightness <br>
• XGA resolution for clear, detailed images <br>
• Built-in 2W speakers <br>
• Extended usage with long lamp life of up to 12,000 hours in Eco model`},
  {id:'cmon', name:'Computer Monitor', price:30000, img:'images/computermonitor.jpg', category:'entertainment', sub:'Televisions & Displays', desc:` A computer monitor is a display device that shows visual output from a computer, providing clear and sharp images for work, gaming, or entertainment. It delivers high-resolution visuals and supports various connectivity options for a seamless computing experience.
<br><br><strong>Features:</strong><br>
• 95% DCI-P3, 100% sRGB <br>
• WQHD ultrawide screen <br>
• 180Hz high refresh rate <br>
• 1500R golden curvature <br>
• RGB ambient light <br>
• Comfortable low blue light <br>
• FreeSync`},
  {id:'oled', name:'OLED TV', price:17988, img:'images/oled.jpg', category:'entertainment', sub:'Televisions & Displays', desc:`An OLED TV is a high-end television that uses organic light-emitting diodes to deliver exceptional picture quality with deep blacks, vibrant colors, and wide viewing angles. It provides an immersive viewing experience for movies, sports, and gaming.
<br><br><strong>Features:</strong><br>
• NQ4 AI Gen2 Processor <br>
• 4K AI Upscaling <br>
• OLED HDR <br>
• PANTONE Validated`},

  // Entertainment -> Audio & Music
  {id:'karaoke', name:'Karaoke Machine', price:18199, img:'images/karaokemachine.jpg', category:'entertainment', sub:'Audio & Music', desc:` A karaoke machine is an audio device that allows users to sing along with music tracks, displaying lyrics on a screen for entertainment at home, parties, or events. It provides fun, interactive, and social singing experiences for all ages.
<br><br><strong>Features:</strong><br>
• Touch Screen LED <br>
• Voice Command (Youtube) <br>
• Portable with built in speaker <br>
• 3-4 hour rechargeable battery <br>
• 2 wireless microphone <br>
• With remote control <br>
• Bluetooth ready`},
  {id:'radio', name:'Portable Radio', price:3651, img:'images/radio.jpg', category:'entertainment', sub:'Audio & Music', desc:` A portable radio is a compact audio device that allows users to listen to AM/FM radio stations on the go. It’s battery-powered and lightweight, making it ideal for outdoor activities, travel, or home use.
<br><br><strong>Features:</strong><br>
• AM/FM radio reception with clear audio <br>
• Lightweight and portable design <br>
• Battery-powered for use anywhere <br>
• Simple controls for tuning and volume <br>
• Built-in speaker for easy listening`},
  {id:'amplifier', name:'Amplifier', price:2950, img:'images/ampli.jpg', category:'entertainment', sub:'Audio & Music', desc:` An amplifier is an electronic device that increases the power of audio signals, enhancing sound volume and quality for speakers or audio systems. It is commonly used in home theaters, concerts, and public address systems to deliver clear and powerful audio.
<br><br><strong>Features:</strong><br>
• USB/SD/BT Player <br>
• FM Radio <br>
• Professional Digital Karaoke IC <br>
• Round Transformer <br>
• High power Output <br>
• Low Noise and Low Distortion <br>
• Professional Electrolytic Capacitance <br>
• PMPO: 300W+300W`},
  {id:'turntable', name:'Turn Table', price:12999, img:'images/turntable.jpg', category:'entertainment', sub:'Audio & Music', desc:`A turntable, or record player, is an audio device that plays vinyl records, delivering warm and authentic sound for music enthusiasts. It combines classic analog audio with modern features for home listening or DJ setups.
<br><br><strong>Features:</strong><br>
• Easy BLUETOOTH® connectivity <br>
• Supports phono and line output thanks to built-in Phono EQ <br>
• Two operating speeds: 33⅓ and 45rpm <br>
• One-step auto play for simple operation <br>
• Newly designed tone arm for rich, clear playback <br>`},
  {id:'headphones', name:'Wireless Headphones', price:2599, img:'images/headphones.jpg', category:'entertainment', sub:'Audio & Music', desc:` The WH-CH520 wireless headphones deliver high-quality sound, long-lasting battery life, and stable connectivity, making them perfect for music, calls, and everyday use. With personalized audio options and a comfortable design, they provide an immersive listening experience wherever you go.
<br><br><strong>Features:</strong><br>
• Stunning sound quality <br>
• Long battery life <br>
• Multipoint connection <br>
• Personalized listening <br>
• All-day comfort <br>
• Enhanced call performance`},

  // Entertainment -> Gaming Gadgets
  {id:'ps', name:'PlayStation', price:44150, img:'images/ps5.jpg', category:'entertainment', sub:'Gaming Gadgets', desc:` PlayStation is a home gaming console that delivers high-quality graphics, immersive gameplay, and access to a wide range of games. It combines powerful hardware with online connectivity to provide an engaging gaming experience for players of all ages.
<br><br><strong>Features:</strong><br>
• High-definition graphics and smooth gameplay <br>
• Access to a vast library of games <br>
• Online multiplayer and streaming capabilities <br>
• Ergonomic wireless controllers for comfortable play <br>
• Storage options for games, apps, and media <br>
• Compatible with VR and other gaming accessories`},
  {id:'nsw2', name:'Nintendo Switch 2', price:27693, img:'images/nsw2.jpg', category:'entertainment', sub:'Gaming Gadgets', desc:` The Nintendo Switch 2 is a versatile gaming console that allows players to enjoy games at home or on the go. With enhanced graphics, faster performance, and hybrid functionality, it delivers an immersive and flexible gaming experience for all types of players.
<br><br><strong>Features:</strong><br>
• 7.9-inch Full HD capacitive touch screen with HDR10 support and 120 Hz VRR <br>
• Custom NVIDIA processor for smooth gaming performance <br>
• 256 GB internal storage <br>
• HDMI video output up to 4K at 60 fps; supports 120 fps at lower resolutions <br>
• Stereo speakers with surround sound effects; built-in monaural microphone <br>
• Multiple USB Type-C ports and 3.5mm audio jack <br>
• Compatible with Nintendo Switch and Nintendo Switch 2 game cards <br>
• Battery life: approximately 2–6.5 hours; charges in ~3 hours`},
  {id:'rwheel', name:'Racing Wheel Simulator', price:14995, img:'images/rwheel.jpg', category:'entertainment', sub:'Gaming Gadgets', desc:` The Racing Wheel Simulator provides an immersive driving experience for gamers, combining realistic force feedback, responsive pedals, and precision steering. Designed for use with PlayStation consoles and Windows PCs, it delivers accurate control and authentic racing feel for both casual and competitive gameplay.
<br><br><strong>Features:</strong><br>
• Realistic 900° lock-to-lock steering with dual-motor force feedback <br>
• Hall-effect steering sensor for precise control <br>
• Self-calibrating pedals with nonlinear brake and textured grips <br>
• Premium materials: anodized aluminum wheel, hand-stitched leather cover, stainless steel shifter paddles <br>
• Compatible with PlayStation 5, 4, 3, and Windows PCs <br>
• Includes racing wheel, pedal unit, power adapter, and user documentation`},
  {id:'controller', name:'Gaming Controller', price:1970, img:'images/controller.jpg', category:'entertainment', sub:'Gaming Gadgets', desc:` The PS5 Wireless Controller offers precise and responsive control for an immersive gaming experience on PlayStation 5. With advanced haptic feedback, adaptive triggers, and an ergonomic design, it enhances gameplay and provides a more realistic connection to your games.
<br><br><strong>Features:</strong><br>
• Advanced haptic feedback for realistic vibrations <br>
• Adaptive triggers that adjust resistance based on in-game actions <br>
• Built-in microphone and headset jack for voice chat <br>
• Ergonomic design for comfort during long gaming sessions <br>
• USB-C charging for faster, convenient charging <br>
• Compatible with PlayStation 5 and select PC games`},
  {id:'vr', name:'VR Headset', price:30334, img:'images/vr.jpg', category:'entertainment', sub:'Gaming Gadgets', desc:` The Oculus Quest is an all-in-one virtual reality headset that delivers immersive gaming experiences without the need for a PC or console. With 128GB of storage, it lets you download and play a wide range of VR games and apps, providing high-quality visuals and intuitive motion tracking for an unparalleled interactive experience.
<br><br><strong>Features:</strong><br>
• All-in-one VR: No PC. No wires. No limits. <br>
• Easy set up <br>
• 128GB internal storage for games and apps <br>
• High-resolution display with smooth graphics <br>
• Precise motion tracking with hand controllersWireless and portable design for gaming anywhere <br>
• Comfortable ergonomic fit for extended play sessions`},

  // Kitchen -> Refrigeration
  {id:'fridge', name:'Refrigerator', price:96000, img:'images/ref.jpg', category:'kitchen', sub:'Refrigeration', desc:` The SAMSUNG RS64R5301B4 is a spacious side-by-side refrigerator designed for modern kitchens, offering large capacity storage, energy-efficient performance, and smart cooling technology. Its sleek design and convenient features make it ideal for keeping food fresh and organized.
<br><br><strong>Features:</strong><br>
• No-Frost <br>
• Power Cool Function <br>
• Power Freeze Function <br>
• All-around cooling <br>
• Space Max Interior <br>
• Water & Ice Dispenser <br>
• Digital Inverter Technology <br>
• Door Alarm <br>
• R600a Refrigerant`},
  {id:'icecream', name:'Ice Cream Maker', price:18995, img:'images/icecream.jpg', category:'kitchen', sub:'Refrigeration', desc:`An ice cream maker is a kitchen appliance designed to make homemade ice cream, sorbet, or frozen yogurt easily. It allows you to control ingredients, flavor, and texture for a delicious and healthy treat anytime.
<br><br><strong>Features:</strong><br>
• Compact size, easy to store <br>
• Variety of choices <br>
• Dishwasher Safe Components <br>
• BPA Free`},
  {id:'icemaker', name:'Ice Maker', price:7699, img:'images/icemaker.jpg', category:'kitchen', sub:'Refrigeration', desc:` The Hicon Commercial Ice Cube Maker is a heavy-duty, automatic ice-making machine designed for businesses, restaurants, and cafes. With a production capacity of 25kg of ice per 24 hours, it ensures a steady supply of clean, high-quality ice for all your commercial needs.
<br><br><strong>Features:</strong><br>
• Automatic ice-making for continuous operation <br>
• Produces up to 25kg of ice per 24 hours <br>
• Durable, heavy-duty construction for commercial use <br>
• Compact design for easy placement in kitchens or bars <br>
• Easy-to-use controls for efficient operation <br>
• Energy-efficient and reliable performance`},
  {id:'dispenser', name:'Water Dispenser', price:5898, img:'images/dispenser.jpg', category:'kitchen', sub:'Refrigeration', desc:` The Fujidenzo FWD1031B is a free-standing water dispenser that provides convenient access to hot, warm, and cold water. Designed for offices, homes, or commercial spaces, it ensures quick, safe, and easy hydration for all users.
<br><br><strong>Features:</strong><br>
• Free-Standing Water Dispenser <br>
• 3 options: Hot, Cold, and Warm <br>
• Child Safety Lock protector – for hot water switch. <br>
• Double Safety Device to prevent overheating <br>
• Compressor Cooling <br>
• Black with silver Trim`},
  {id:'winecooler', name:'Wine Cooler', price:6590, img:'images/wine.jpg', category:'kitchen', sub:'Refrigeration', desc:` The Lecon Wine Cabinet is a compact, constant-temperature wine refrigerator designed for household and commercial use. It stores wine, cigars, or beverages at the perfect temperature, preserving flavor and quality while offering a stylish and convenient storage solution.
<br><br><strong>Features:</strong><br>
• Constant temperature control for optimal wine and beverage storage <br>
• Compact design suitable for home or commercial use <br>
• Can store 12–16 bottles depending on model <br>
• Suitable for wine, cigars, or other beverages <br>
• Energy-efficient and quiet operation <br>
• Sleek electronic display for easy temperature monitoring`},

  // Kitchen -> Cooking Appliances
  {id:'airfryer', name:'Air Fryer', price:2599, img:'images/afryer.jpg', category:'kitchen', sub:'Cooking Appliances', desc:`The Xiaomi Smart Air Fryer lets you cook delicious and healthy meals with little to no oil. With smart controls and preset cooking modes, it offers a convenient and efficient way to fry, bake, roast, and grill your favorite foods.
<br><br><strong>Features:</strong><br>
• Healthy cooking with little or no oil <br>
• Multiple preset cooking modes for frying, baking, roasting, and grilling <br>
• Smart control via app or built-in interface <br>
• Adjustable temperature and timer settings <br>
• Easy-to-clean non-stick basket <br>
• Compact design suitable for kitchens of all sizes`},
  {id:'ricecook', name:'Rice Cooker', price:2649, img:'images/ricecooker.jpg', category:'kitchen', sub:'Cooking Appliances', desc:`The Xiaomi 3L Smart Multifunctional Rice Cooker offers convenient and precise cooking for perfect rice and a variety of dishes. With app controls, it allows you to schedule, monitor, and customize cooking settings from anywhere, making it ideal for modern kitchens.
<br><br><strong>Features:</strong><br>
• Smart app control for remote cooking and monitoring <br>
• 3L capacity, suitable for small to medium households <br>
• Multifunctional cooking: rice, porridge, soup, and more <br>
• Precise temperature and timing control for perfect results <br>
• Non-stick inner pot for easy cleaning <br>
• Compact and stylish design for modern kitchens`},
  {id:'stove', name:'Stove', price:99999, img:'images/stove.jpg', category:'kitchen', sub:'Cooking Appliances', desc:` The LG Electronics Oven Range combines powerful natural gas burners with a spacious oven and self-cleaning functionality. Designed for modern kitchens, it offers precise cooking control, convenience, and a sleek stainless steel finish.
<br><br><strong>Features:</strong><br>
• 5 natural gas burners for versatile cooking <br>
• Spacious oven with self-cleaning function <br>
• Even heat distribution for consistent results <br>
• Sleek stainless steel design that complements any kitchen <br>
• Easy-to-use controls for precise cooking <br>
• Durable and energy-efficient performance `},
  {id:'oven', name:'Oven', price:89995, img:'images/oven.jpg', category:'kitchen', sub:'Cooking Appliances', desc:`The Classic Matte Black 60cm Electric Oven (SFP6301TVN) offers professional-grade cooking with precise temperature control and pyrolytic self-cleaning. Its sleek design and user-friendly features make it perfect for modern kitchens, delivering consistent results for baking, roasting, and grilling.
<br><br><strong>Features:</strong><br>
• 60cm electric oven with spacious interior <br>
• Pyrolytic self-cleaning function for easy maintenance <br>
• Precise temperature control for consistent cooking <br>
• Sleek matte black finish for a modern kitchen look <br>
• Multiple cooking modes: baking, roasting, grilling <br>
• Energy-efficient and durable performance`},
  {id:'toaster', name:'Toaster', price:1475, img:'images/toaster.jpg', category:'kitchen', sub:'Cooking Appliances', desc:` The Asahi BT-040 2-Slice Bread Toaster is a compact and efficient kitchen appliance designed for quick and even toasting. Perfect for home or office use, it delivers perfectly browned bread slices every time.
<br><br><strong>Features:</strong><br>
• 2 Bread Slices <br>
• Pop-up bread toaster <br>
• 800W <br>
• 6 browning settings <br>
• With thermal cut-off fuse <br>
• With stop function <br>
• With defrost function <br>
• With warm-up function <br>
• Cord winder storage <br>
• Pull out bread crumb tray`},

  // Kitchen -> Food Preparation
  {id:'blender', name:'Blender', price:3085, img:'images/blender.jpg', category:'kitchen', sub:'Food Preparation', desc:`The Hanabishi HJB100 Juice Blender is a heavy-duty kitchen appliance designed for home use, allowing you to blend fruits, vegetables, and other ingredients quickly and efficiently. Its 1.7-liter capacity makes it ideal for preparing smoothies, juices, and other blended drinks for the whole family.
<br><br><strong>Features:</strong><br>
• Capacity: 1.7 liter <br>
• 2 speeds control with pulse function <br>
• Powerful motor with overheat and overcurrent double protection <br>
• 6-blade design for efficient blending, chopping, and ice crushing with safety lock`},
  {id:'fprocessor', name:'Food Processor', price:285, img:'images/fprocessor.jpg', category:'kitchen', sub:'Food Preparation', desc:`This 2L Kitchen Meat Grinder and Food Processor is a versatile appliance that quickly chops, minces, and processes meat, vegetables, and other ingredients. With fast and slow two-gear control, it allows precise grinding for both soft and hard foods, making meal preparation faster and easier.
<br><br><strong>Features:</strong><br>
• Easily chops vegetables, minces meat, and prepares sauces or baby food <br>
• Dual-speed control for soft or hard ingredients <br>
• 2L stainless steel container for family-sized portions <br>
• Powerful 250W motor for efficient and stable performance <br>
• Durable, easy-to-clean stainless steel construction <br>
• Safe and simple operation for hygienic, hands-free food preparation`},
  {id:'mgrinder', name:'Meat Grinder', price:8790, img:'images/mgrinder.jpg', category:'kitchen', sub:'Food Preparation', desc:`The BOBI Heavy-Duty Food Processor and Meat Grinder is a commercial-grade appliance built for high-capacity kitchens. With the ability to process up to 120 kg of meat per hour, its stainless steel construction and powerful motor ensure reliable, efficient, and hygienic food preparation for restaurants, butcheries, and catering businesses.
<br><br><strong>Features:</strong><br>
• Heavy-duty commercial meat grinder and food processor<br>
• Processes up to 120 kg of meat per hour<br>
• Durable stainless steel body for hygiene and long-lasting use<br>
• Powerful motor for fast and stable operation<br>
• Suitable for meat, vegetables, and other food processing tasks<br>
• Easy to clean and maintain for daily commercial use`},
  {id:'cgrinder', name:'Coffee Grinder', price:8790, img:'images/cgrinder.jpg', category:'kitchen', sub:'Food Preparation', desc:`The Delonghi KG 89 Electric Coffee Grinder allows you to grind coffee beans quickly and evenly, delivering fresh, aromatic coffee for home or office use. Its compact design and easy-to-use controls make it convenient for daily brewing.
<br><br><strong>Features:</strong><br>
• 120 g transparent bean container with lid.<br>
• High-quality coffee grinder.<br>
• Grind adjustment from coarse to fine.<br>
• Transparent, exchangeable coffee powder container.<br>
• Cup selection switch.`},
  {id:'smixer', name:'Stand Mixer', price:8790, img:'images/mixer.jpg', category:'kitchen', sub:'Food Preparation', desc:`The SM-50TQ Precision Master™ 5.5-Quart Stand Mixer is a versatile and powerful kitchen appliance designed for baking enthusiasts and professionals. With a large 5.5-quart capacity and multiple speed settings, it effortlessly mixes, kneads, whips, and beats ingredients for perfect results every time.
<br><br><strong>Features:</strong><br>
• 5.5-quart large-capacity bowl for family or professional baking <br>
• Multiple speed settings for precise mixing control <br>
• Powerful motor for consistent and efficient performance <br>
• Includes attachments for mixing, kneading, whipping, and beating <br>
• Durable construction for long-lasting use <br>
• Stable base design to prevent slipping during operation <br>
• Easy-to-clean components for convenient maintenance`},

  // If you later want duplicates intentionally, you can add them here as separate ids.
];

/* ---------- UI & state ---------- */
let currentCategory = 'household';
let currentSubcat = null;

/* initialize top category boxes */
function renderTopCats(){
  const cats = document.querySelectorAll('.cat-box');
  cats.forEach(el=>{
    el.classList.toggle('active', el.dataset.cat === currentCategory);
    el.onclick = ()=>{
      currentCategory = el.dataset.cat;
      currentSubcat = null;
      renderTopCats();
      renderSubcats();
      renderProducts();
      scrollToTop();
    };
  });
}

/* build subcategory buttons from PRODUCTS for current category */
function renderSubcats(){
  const area = document.getElementById('subcat-area');
  area.innerHTML = '';
  const subs = [...new Set(PRODUCTS.filter(p=>p.category===currentCategory).map(p=>p.sub))];
  if(subs.length===0) return;
  const wrapper = document.createElement('div');
  wrapper.className = 'subcats';
  // "All" button
  const allBtn = document.createElement('button');
  allBtn.className = 'subcat-btn' + (currentSubcat===null ? ' active':'');
  allBtn.textContent = 'All';
  allBtn.onclick = ()=>{ currentSubcat = null; renderSubcats(); renderProducts(); };
  wrapper.appendChild(allBtn);
  subs.forEach(s=>{
    const b = document.createElement('button');
    b.className = 'subcat-btn' + (currentSubcat===s ? ' active':'');
    b.textContent = s;
    b.onclick = ()=>{ currentSubcat = s; renderSubcats(); renderProducts(); };
    wrapper.appendChild(b);
  });
  area.appendChild(wrapper);
}

/* render product cards for category & subcategory */
function renderProducts(){
  const grid = document.getElementById('product-grid');
  grid.innerHTML = '';
  const list = PRODUCTS.filter(p=> p.category===currentCategory && (currentSubcat ? p.sub===currentSubcat : true));
  list.forEach(p=>{
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
      <div class="img"><img src="${p.img}" alt="${p.name}" onerror="this.style.display='none'"></div>
      <div class="product-name">${p.name}</div>
      <div class="product-price">₱${p.price.toLocaleString()}</div>
      <div style="color:var(--muted); font-size:13px; margin-bottom:8px;">${p.sub}</div>
<div class="product-actions">
  <button class="btn-add" onclick="addToCart('${p.id}')">Add to Cart</button>
  <button class="btn-buy" onclick="buyNow('${p.id}')">Buy Now</button>
  <button class="btn-view" onclick="showDetails('${p.id}')">View Details</button>
</div>
    `;
    grid.appendChild(card);
  });
}

/* ---------- CART LOGIC ---------- */
/* cart: array of {id, name, price, qty, img} */
let cart = JSON.parse(localStorage.getItem('st_cart')) || [];

function saveCart(){ localStorage.setItem('st_cart', JSON.stringify(cart)); updateCartCount(); }

function updateCartCount(){
  const count = cart.reduce((s,i)=> s + (i.qty||1), 0);
  document.getElementById('cart-count').textContent = count;
}

/* find product by id in PRODUCTS */
function findProduct(id){ return PRODUCTS.find(p=>p.id===id); }

/* addToCart by product id */
function addToCart(id){
  const p = findProduct(id);
  if(!p) return alert('Product not found');
  const idx = cart.findIndex(i=>i.id===id);
  if(idx>-1) cart[idx].qty = (cart[idx].qty||1) + 1;
  else cart.push({ id:p.id, name:p.name, price:p.price, qty:1, img:p.img });
  saveCart(); renderCart();
}

/* buyNow: add then open cart/payment */
function buyNow(id){
  addToCart(id);
  setTimeout(()=>{ scrollToCart(); openPayment(); }, 150);
}

/* render cart items under "Your Cart" */
function renderCart(){
  const container = document.getElementById('cart-items');
  container.innerHTML = '';
  if(cart.length===0){
    container.innerHTML = '<p style="text-align:center;color:#777;">Your cart is empty.</p>';
    document.getElementById('cart-total').textContent = 'Total: ₱0';
    updateCartCount();
    return;
  }
  let total = 0;
  cart.forEach((it, idx)=>{
    total += it.price * it.qty;
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.innerHTML = `
      <div class="cart-left">
        <img src="${it.img}" alt="${it.name}" onerror="this.style.display='none'">
        <div class="cart-meta">
          <div style="font-weight:bold;">${it.name}</div>
          <div style="color:var(--muted); font-size:13px;">₱${it.price.toLocaleString()}</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="qty-controls">
          <button onclick="changeQty(${idx}, -1)">-</button>
          <span id="qty-${idx}" style="min-width:24px; text-align:center;">${it.qty}</span>
          <button onclick="changeQty(${idx}, 1)">+</button>
        </div>
        <div style="min-width:100px; text-align:right;">₱${(it.price*it.qty).toLocaleString()}</div>
        <button class="remove-btn" onclick="removeItem(${idx})">Remove</button>
      </div>
    `;
    container.appendChild(div);
  });
  document.getElementById('cart-total').textContent = 'Total: ₱' + total.toLocaleString();
  updateCartCount();
}

/* change quantity */
function changeQty(index, delta){
  cart[index].qty = Math.max(1, (cart[index].qty||1) + delta);
  saveCart(); renderCart();
}

/* remove item */
function removeItem(index){
  if(!confirm('Remove this item?')) return;
  cart.splice(index,1);
  saveCart(); renderCart();
}

/* clear cart */
function clearCart(){
  if(!confirm('Clear entire cart?')) return;
  cart = []; saveCart(); renderCart();
}

/* scroll to cart */
function scrollToCart(){ document.getElementById('cart-section').scrollIntoView({behavior:'smooth', block:'start'}); renderCart(); }

/* scroll to top */
function scrollToTop(){ window.scrollTo({top:0, behavior:'smooth'}); }

/* ---------- PAYMENT UI ---------- */
function openPayment(){
  if(cart.length===0){ alert('Cart empty. Add items first.'); return; }
  // populate payment items
  const payItems = document.getElementById('pay-items');
  payItems.innerHTML = '';
  let total = 0;
  cart.forEach(it => { total += it.price * it.qty; payItems.innerHTML += `<div style="padding:6px 0;">${it.name} x${it.qty} — ₱${(it.price*it.qty).toLocaleString()}</div>`; });
  document.getElementById('pay-total').textContent = '₱' + total.toLocaleString();
  document.getElementById('payment').style.display = 'block';
  setTimeout(()=> document.getElementById('payment').scrollIntoView({behavior:'smooth', block:'start'}), 80);
}

/* hide payment */
function closePayment(){ document.getElementById('payment').style.display = 'none'; }

/* show card fields when Card is chosen */
document.addEventListener('change', (e)=>{
  if(e.target.name === 'paymethod'){
    const val = document.querySelector('input[name="paymethod"]:checked')?.value;
    document.getElementById('card-fields').style.display = (val==='Card') ? 'block' : 'none';
  }
});

/* complete payment (simulated) */
function completePayment(){
  const address = document.getElementById('address').value.trim();
  const method = document.querySelector('input[name="paymethod"]:checked')?.value;
  if(!address) return alert('Please add delivery address.');
  if(!method) return alert('Please choose a payment method.');
  if(method === 'Card'){
    const cn = document.getElementById('card-number').value.replace(/\s/g,'');
    const nm = document.getElementById('card-name').value.trim();
    const exp = document.getElementById('card-exp').value.trim();
    const cvv = document.getElementById('card-cvv').value.trim();
    if(!nm||!cn||!exp||!cvv) return alert('Please fill card details.');
    if(cn.replace(/\D/g,'').length < 13) return alert('Enter a valid card number.');
  }
  alert('Payment successful!\nMode: ' + method + '\nDelivery to: ' + address);
  // clear cart & payment fields
  cart = []; saveCart(); renderCart();
  document.getElementById('address').value = '';
  document.querySelectorAll('input[name="paymethod"]').forEach(i=>i.checked=false);
  document.getElementById('card-fields').style.display='none';
  document.getElementById('payment').style.display='none';
}

/* ---------- startup ---------- */
function init(){
  renderTopCats();
  renderSubcats();
  renderProducts();
  renderCart();
  updateCartCount();
}

/* hook up top-cat boxes */
document.querySelectorAll('.cat-box').forEach(box=>{
  box.addEventListener('click', ()=>{
    currentCategory = box.dataset.cat;
    currentSubcat = null;
    renderTopCats();
    renderSubcats();
    renderProducts();
    scrollToTop();
  });
});

function showDetails(id) {
  const p = PRODUCTS.find(pr => pr.id === id);
  if (!p) return;

  const desc = p.desc;
  document.getElementById('descContent').innerHTML = `
    <img src="${p.img}" alt="${p.name}">
    <h2>${p.name}</h2>
    <p><strong>₱${p.price.toLocaleString()}</strong></p>
    <p style="color:#555;">${p.sub}</p>
    <div style="text-align:left; margin:10px auto; max-width:600px;">${desc}</div>
    <div style="margin-top:15px;">
      <button class="btn-add" onclick="addToCart('${p.id}')">Add to Cart</button>
      <button class="btn-buy" onclick="buyNow('${p.id}')">Buy Now</button>
    </div>
  `;

  document.getElementById('descOverlay').style.display = 'flex';
}

function closeOverlay() {
  document.getElementById('descOverlay').style.display = 'none';
}

/* Optional: close when clicking outside the content */
window.addEventListener('click', e => {
  const overlay = document.getElementById('descOverlay');
  if (e.target === overlay) closeOverlay();
});
window.addEventListener('load', init);




