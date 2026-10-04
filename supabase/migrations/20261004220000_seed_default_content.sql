-- Initial Hashttick learning content.
-- Idempotent: creates the default collections when absent and fills them without deleting existing content.
DO $$
DECLARE c_id bigint;
BEGIN
  SELECT id INTO c_id FROM public.collections WHERE name='English Essentials A1' ORDER BY id LIMIT 1;
  IF c_id IS NULL THEN
    INSERT INTO public.collections(name,description,type,cover_color) VALUES ('English Essentials A1','واژه‌های پایه و ضروری انگلیسی برای شروع یادگیری.','dictionary','#8b7cff') RETURNING id INTO c_id;
  END IF;
  INSERT INTO public.collection_words(collection_id,english,farsi,example,unit) VALUES
    (c_id,'hello','سلام','Hello, nice to meet you.',1),
    (c_id,'goodbye','خداحافظ','Goodbye, see you tomorrow.',1),
    (c_id,'please','لطفاً','Please open the door.',1),
    (c_id,'thanks','ممنون','Thanks for your help.',1),
    (c_id,'sorry','متأسفم','Sorry, I am late.',1),
    (c_id,'yes','بله','Yes, I understand.',1),
    (c_id,'no','نه','No, thank you.',1),
    (c_id,'friend','دوست','My friend lives nearby.',1),
    (c_id,'family','خانواده','My family is small.',1),
    (c_id,'home','خانه','I am going home.',1),
    (c_id,'school','مدرسه','The school is near my home.',2),
    (c_id,'work','کار','I go to work every day.',2),
    (c_id,'book','کتاب','This book is interesting.',2),
    (c_id,'water','آب','Please drink some water.',2),
    (c_id,'food','غذا','The food is ready.',2),
    (c_id,'coffee','قهوه','I drink coffee in the morning.',2),
    (c_id,'morning','صبح','I study in the morning.',2),
    (c_id,'night','شب','The city is quiet at night.',2),
    (c_id,'today','امروز','Today is a good day.',2),
    (c_id,'tomorrow','فردا','See you tomorrow.',2),
    (c_id,'big','بزرگ','The room is big.',3),
    (c_id,'small','کوچک','I have a small bag.',3),
    (c_id,'good','خوب','This is a good idea.',3),
    (c_id,'bad','بد','That was a bad mistake.',3),
    (c_id,'new','جدید','I bought a new phone.',3),
    (c_id,'old','قدیمی','This is an old house.',3),
    (c_id,'happy','خوشحال','She looks happy.',3),
    (c_id,'tired','خسته','I am tired after work.',3),
    (c_id,'fast','سریع','This train is fast.',3),
    (c_id,'slow','آهسته','Please speak slowly.',3)
  ON CONFLICT DO NOTHING;

  SELECT id INTO c_id FROM public.collections WHERE name='Everyday English A2' ORDER BY id LIMIT 1;
  IF c_id IS NULL THEN
    INSERT INTO public.collections(name,description,type,cover_color) VALUES ('Everyday English A2','واژه‌های کاربردی برای مکالمه و زندگی روزمره.','dictionary','#8b7cff') RETURNING id INTO c_id;
  END IF;
  INSERT INTO public.collection_words(collection_id,english,farsi,example,unit) VALUES
    (c_id,'arrive','رسیدن','We arrive at the station at eight.',1),
    (c_id,'leave','ترک کردن','I leave home at seven.',1),
    (c_id,'choose','انتخاب کردن','Choose the best answer.',1),
    (c_id,'decide','تصمیم گرفتن','I need time to decide.',1),
    (c_id,'remember','به یاد آوردن','Remember to call me.',1),
    (c_id,'forget','فراموش کردن','Do not forget your keys.',1),
    (c_id,'borrow','قرض گرفتن','Can I borrow your pen?',1),
    (c_id,'lend','قرض دادن','Can you lend me a book?',1),
    (c_id,'invite','دعوت کردن','I want to invite my friends.',1),
    (c_id,'travel','سفر کردن','We travel in summer.',1),
    (c_id,'ticket','بلیط','I bought a train ticket.',2),
    (c_id,'station','ایستگاه','The station is crowded.',2),
    (c_id,'address','آدرس','Please send me your address.',2),
    (c_id,'message','پیام','I sent you a message.',2),
    (c_id,'meeting','جلسه','The meeting starts at ten.',2),
    (c_id,'problem','مشکل','We have a small problem.',2),
    (c_id,'answer','پاسخ','I know the answer.',2),
    (c_id,'question','سؤال','That is a good question.',2),
    (c_id,'important','مهم','This meeting is important.',2),
    (c_id,'possible','ممکن','Is it possible today?',2),
    (c_id,'different','متفاوت','We have different opinions.',3),
    (c_id,'enough','کافی','Do we have enough time?',3),
    (c_id,'usually','معمولاً','I usually walk to work.',3),
    (c_id,'sometimes','گاهی','Sometimes I cook at home.',3),
    (c_id,'already','قبلاً','I have already finished.',3),
    (c_id,'early','زود','I woke up early.',3),
    (c_id,'late','دیر','He arrived late.',3),
    (c_id,'careful','محتاط','Be careful on the stairs.',3),
    (c_id,'available','در دسترس','The manager is available now.',3),
    (c_id,'comfortable','راحت','This chair is comfortable.',3)
  ON CONFLICT DO NOTHING;

  SELECT id INTO c_id FROM public.collections WHERE name='Practical English B1' ORDER BY id LIMIT 1;
  IF c_id IS NULL THEN
    INSERT INTO public.collections(name,description,type,cover_color) VALUES ('Practical English B1','واژه‌های کاربردی سطح متوسط برای مکالمه و مطالعه.','dictionary','#8b7cff') RETURNING id INTO c_id;
  END IF;
  INSERT INTO public.collection_words(collection_id,english,farsi,example,unit) VALUES
    (c_id,'improve','بهبود دادن','Practice can improve your English.',1),
    (c_id,'achieve','به دست آوردن','You can achieve your goal.',1),
    (c_id,'avoid','اجتناب کردن','Try to avoid unnecessary stress.',1),
    (c_id,'compare','مقایسه کردن','Do not compare yourself with others.',1),
    (c_id,'consider','در نظر گرفتن','Please consider this option.',1),
    (c_id,'require','نیاز داشتن','This job requires experience.',1),
    (c_id,'provide','فراهم کردن','We provide useful information.',1),
    (c_id,'increase','افزایش دادن','Exercise can increase your energy.',1),
    (c_id,'reduce','کاهش دادن','We need to reduce costs.',1),
    (c_id,'develop','توسعه دادن','I want to develop new skills.',1),
    (c_id,'experience','تجربه','This job gives me useful experience.',2),
    (c_id,'skill','مهارت','Communication is an important skill.',2),
    (c_id,'knowledge','دانش','Knowledge grows with practice.',2),
    (c_id,'opportunity','فرصت','This is a great opportunity.',2),
    (c_id,'decision','تصمیم','It was a difficult decision.',2),
    (c_id,'reason','دلیل','Tell me the reason.',2),
    (c_id,'result','نتیجه','The result was better than expected.',2),
    (c_id,'process','فرآیند','Learning is a long process.',2),
    (c_id,'success','موفقیت','Success takes time.',2),
    (c_id,'failure','شکست','Failure can teach us useful lessons.',2),
    (c_id,'environment','محیط','A quiet environment helps me study.',3),
    (c_id,'relationship','رابطه','Trust is important in a relationship.',3),
    (c_id,'support','حمایت','Thank you for your support.',3),
    (c_id,'confident','بااعتمادبه‌نفس','She feels confident today.',3),
    (c_id,'curious','کنجکاو','He is curious about science.',3),
    (c_id,'reliable','قابل اعتماد','We need a reliable system.',3),
    (c_id,'effective','مؤثر','This method is effective.',3),
    (c_id,'specific','مشخص','Give me a specific example.',3),
    (c_id,'average','متوسط','The average score was high.',3),
    (c_id,'likely','محتمل','It is likely to rain.',3)
  ON CONFLICT DO NOTHING;

  UPDATE public.collections c
     SET total_words=(SELECT count(*) FROM public.collection_words cw WHERE cw.collection_id=c.id);
END $$;
